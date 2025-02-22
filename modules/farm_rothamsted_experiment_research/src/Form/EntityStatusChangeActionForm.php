<?php

namespace Drupal\farm_rothamsted_experiment_research\Form;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides an entity status change confirmation form.
 */
class EntityStatusChangeActionForm extends ConfirmFormBase {

  /**
   * The tempstore factory.
   *
   * @var \Drupal\Core\TempStore\SharedTempStore
   */
  protected $tempStore;

  /**
   * The entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The assets to create logs for.
   *
   * @var \Drupal\Core\Entity\EntityInterface[]
   */
  protected $entities;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityFieldManagerInterface $entityFieldManager,
    protected AccountInterface $user,
    PrivateTempStoreFactory $temp_store_factory,
  ) {
    $this->tempStore = $temp_store_factory->get('research_entity_status_change_confirm');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('current_user'),
      $container->get('tempstore.private'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    // Get entity type ID from the route because ::buildForm has not yet been
    // called.
    $entity_type_id = $this->getRouteMatch()->getParameter('entity_type_id');
    return $entity_type_id . '_status_change_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(
      count($this->entities),
      'Are you sure you want to change the status of this @item?',
      'Are you sure you want to change the status of these @items?',
      [
        '@item' => $this->entityType->getSingularLabel(),
        '@items' => $this->entityType->getPluralLabel(),
      ],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    if ($this->entityType->hasLinkTemplate('collection')) {
      return new Url('entity.' . $this->entityType->id() . '.collection');
    }
    else {
      return new Url('<front>');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Continue');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL) {
    $this->entityType = $this->entityTypeManager->getDefinition($entity_type_id);
    $this->entities = $this->tempStore->get($this->user->id() . ':' . $entity_type_id);
    if (empty($entity_type_id) || empty($this->entities)) {
      return new RedirectResponse($this->getCancelUrl()
        ->setAbsolute()
        ->toString());
    }

    // Load the status field from the entity type.
    $field_definitions = $this->entityFieldManager->getFieldDefinitions($this->entityType->id(), $this->entityType->id());
    if (!isset($field_definitions['status'])) {
      return $form;
    }

    // Get field options.
    $options = [];
    $entity_storage = $this->entityTypeManager->getStorage($this->entityType->id());
    $default_entity = $entity_storage->create();
    if ($options_provider = $field_definitions['status']->getOptionsProvider($field_definitions['status']->getMainPropertyName(), $default_entity)) {
      $options = $options_provider->getPossibleOptions($this->user);
    }

    // Build status field.
    $form['status'] = [
      '#type' => 'select',
      '#title' => $this->t('Status'),
      '#description' => $field_definitions['status']->getDescription(),
      '#options' => $options,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Filter out entities the user doesn't have access to.
    $inaccessible_entities = [];
    $accessible_entities = [];
    foreach ($this->entities as $entity) {
      if (!$entity->access('update', $this->currentUser())) {
        $inaccessible_entities[] = $entity;
        continue;
      }
      $accessible_entities[] = $entity;
    }

    // Update flags on accessible entities.
    $total_count = 0;
    foreach ($accessible_entities as $entity) {
      if ($status_field = $entity->get("status")) {

        // Empty the flag field.
        $status_field->setValue($form_state->getValue('status'));

        // Validate the entity before saving.
        $violations = $entity->validate();
        if ($violations->count() > 0) {
          $this->messenger()->addWarning(
            $this->t('Could not change status of <a href=":entity_link">%entity_label</a>: validation failed:',
              [
                ':entity_link' => $entity->toUrl()->setAbsolute()->toString(),
                '%entity_label' => $entity->label(),
              ],
            ),
          );
          foreach ($violations as $violation) {
            $this->messenger()->addWarning($violation->getMessage());
          }
          continue;
        }

        $entity->save();
        $total_count++;
      }
    }

    // Add warning message for inaccessible entities.
    if (!empty($inaccessible_entities)) {
      $inaccessible_count = count($inaccessible_entities);
      $this->messenger()->addWarning($this->formatPlural(
        $inaccessible_count,
        'Could not change status of @count @item because you do not have the necessary permissions.',
        'Could not change status of @count @items because you do not have the necessary permissions.',
        [
          '@item' => $this->entityType->getSingularLabel(),
          '@items' => $this->entityType->getPluralLabel(),
        ],
      ));
    }

    // Add confirmation message.
    if (!empty($total_count)) {
      $this->messenger()->addStatus($this->formatPlural($total_count, 'Changed status of @count @item.', 'Changed status of @count @items', [
        '@item' => $this->entityType->getSingularLabel(),
        '@items' => $this->entityType->getPluralLabel(),
      ]));
    }
    $this->tempStore->delete($this->currentUser()->id() . ':' . $this->entityType->id());
    $url = $this->getCancelUrl()
      ->setOption('query', ['status' => $form_state->getValue('status')]);
    $form_state->setRedirectUrl($url);
    $form_state->setIgnoreDestination();
  }

}
