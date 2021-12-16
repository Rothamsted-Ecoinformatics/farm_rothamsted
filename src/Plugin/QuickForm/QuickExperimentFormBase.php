<?php

namespace Drupal\farm_rothamsted\Plugin\QuickForm;

use Drupal\asset\Entity\AssetInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\farm_group\GroupMembershipInterface;
use Drupal\farm_quick\Plugin\QuickForm\QuickFormBase;
use Drupal\farm_quick\Traits\QuickPrepopulateTrait;
use Drupal\taxonomy\TermInterface;
use Drupal\user\UserInterface;
use Psr\Container\ContainerInterface;

/**
 * Base class for experiment plan quick forms.
 */
abstract class QuickExperimentFormBase extends QuickFormBase {

  use QuickPrepopulateTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group membership service.
   *
   * @var \Drupal\farm_group\GroupMembershipInterface
   */
  protected $groupMembership;

  /**
   * Boolean indicating if the quick form should have a tractor field.
   *
   * @var bool
   */
  protected $tractorField = FALSE;

  /**
   * The machinery equipment group names to use.
   *
   * @var string[]
   */
  protected $machineryGroupNames = [];

  /**
   * Constructs a QuickFormBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\farm_group\GroupMembershipInterface $group_membership
   *   The group membership service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MessengerInterface $messenger, EntityTypeManagerInterface $entity_type_manager, GroupMembershipInterface $group_membership) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $messenger);
    $this->entityTypeManager = $entity_type_manager;
    $this->groupMembership = $group_membership;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('messenger'),
      $container->get('entity_type.manager'),
      $container->get('group.membership'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Load prepopulated assets.
    $plots = $this->getPrepopulatedEntities('asset');
    $default_plots = implode(', ', array_map(function (AssetInterface $asset) {
      return $asset->label();
    }, $plots));

    // Plot field.
    $form['plot'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Plots'),
      '#description' => $this->t('Select plot assets.'),
      // @todo Decide on a widget for selecting plot assets.
      '#default_value' => $default_plots ?: 'TBD',
      '#required' => TRUE,
      '#weight' => -10,
    ];

    // Build the tractor field if required.
    if ($this->tractorField) {
      $tractor_options = $this->getGroupMemberOptions(['Tractor'], ['equipment']);
      $form['tractor'] = [
        '#type' => 'select',
        '#title' => $this->t('Tractor'),
        '#options' => $tractor_options,
        '#required' => TRUE,
        '#weight' => 10,
      ];
    }

    // Build the machinery field if required.
    if (!empty($this->machineryGroupNames)) {
      $equipment_options = $this->getGroupMemberOptions($this->machineryGroupNames, ['equipment']);
      $form['machinery'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Machinery'),
        '#options' => $equipment_options,
        '#weight' => 10,
      ];
    }

    // Operator
    $form['users'] = $this->buildManagerOperatorElement($weight = 20);

    $form['date'] = [
      '#type' => 'datelist',
      '#title' => $this->t('Date'),
      '#default_value' => new DrupalDateTime(),
      '#date_part_order' => ['year', 'month', 'day'],
      '#required' => TRUE,
      '#date_year_range' => '-15:+15',
      '#weight' => 30,
    ];

    $form['time'] = [
      '#type' => 'number',
      '#title' => $this->t('Hours spent'),
      '#field_suffix' => $this->t('hours'),
      '#required' => TRUE,
      '#weight' => 40,
    ];

    $form['notes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Notes'),
      '#weight' => 50,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * Helper function to load group members of a given asset type.
   *
   * @param string[] $group_names
   *   The group names to query.
   * @param string[] $asset_types
   *   The asset types to limit group members to.
   *
   * @return array
   *   An array of asset labels keyed by the asset ID.
   */
  protected function getGroupMemberOptions(array $group_names, array $asset_types = []): array {
    $asset_storage = $this->entityTypeManager->getStorage('asset');

    // Load the groups.
    $group_ids = $asset_storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('status', 'active')
      ->condition('type', 'group')
      ->condition('name', $group_names, 'IN')
      ->execute();

    // Bail if there are no groups.
    if (empty($group_ids)) {
      return [];
    }

    // Load the group members.
    $groups = $asset_storage->loadMultiple($group_ids);
    $group_members = $this->groupMembership->getGroupMembers($groups, TRUE);

    // If specified, filter group members to a single asset type.
    if (!empty($asset_type)) {
      $group_members = array_filter($group_members, function (AssetInterface $asset) use ($asset_types) {
        return in_array($asset->getEntityTypeId(), $asset_types);
      });
    }

    // Build group options.
    $group_options = array_map(function (AssetInterface $asset) {
      return $asset->label();
    }, $group_members);
    natsort($group_options);

    return $group_options;
  }

  /**
   * Helper function to build a sorted option list of taxonomy terms.
   *
   * @param string $vocabulary_name
   *   The name of vocabulary.
   *
   * @return array
   *   An array of term labels indexed by term ID and sorted alphabetically.
   */
  protected function getTermOptions(string $vocabulary_name): array {

    // Load active terms.
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties([
      'vid' => $vocabulary_name,
      'status' => 1,
    ]);

    // Build options.
    $options = array_map(function (TermInterface $term) {
      return $term->label();
    }, $terms);
    natsort($options);

    return $options;
  }

  /**
   * Helper function to build a sorted option list of child taxonomy terms.
   *
   * @param string $vocabulary_name
   *   The name of vocabulary.
   * @param string $term_name
   *   The name of parent taxonomy term.
   *
   * @return array
   *   An array of taxonomy labels ordered alphabetically.
   */
  protected function getChildTermOptions(string $vocabulary_name, string $term_name): array {

    // Build array of options.
    $options = [];

    // Search for a parent term.
    $term_storage = $this->entityTypeManager->getSTorage('taxonomy_term');
    $matching_terms = $term_storage->loadByProperties([
      'vid' => $vocabulary_name,
      'name' => $term_name,
      'status' => 1,
    ]);

    // If a parent term exists.
    if ($parent_term = reset($matching_terms)) {

      // Build option for each active child term.
      foreach ($term_storage->loadChildren($parent_term->id()) as $term) {
        if ($term->get('status')->value) {
          $options[$term->id()] = $term->label();
        }
      }
    }

    // Sort options.
    natsort($options);

    return $options;
  }

  /**
   * Helper function to build crop element.
   *
   * @param int $weight
   *   For ordering elements on form.
   *
   * @return array
   *   An array containing form configuration.
   */
  protected function buildCropElement(int $weight = 1): array {

    // Build crop options from the plant types term.
    $plant_types_options = $this->getTermOptions('plant_type');

    // Crops - checkboxes - required.
    $element = [
      '#type' => 'checkboxes',
      '#target_type' => 'taxonomy_term',
      '#title' => $this->t('Crops'),
      '#options' => $plant_types_options,
      '#multiple' => TRUE,
      '#required' => TRUE,
      '#weight' => $weight,
    ];

    return $element;
  }

  /**
   * Helper function to build manager operator element.
   *
   * @param int $weight
   *   For ordering elements on form.
   *
   * @return array
   *   An array containing form configuration.
   */
  protected function buildManagerOperatorElement(int $weight = 1): array {

    // Query active, non-admin users with the farm_operator role.
    $user_ids = $this->entityTypeManager->getStorage('user')->getQuery()
      ->accessCheck(TRUE)
      ->condition('status', 1)
      ->condition('uid', '1', '>')
      ->condition('roles', 'farm_operator')
      ->execute();
    $users = $this->entityTypeManager->getStorage('user')->loadMultiple($user_ids);

    // Build user options.
    $user_options = array_map(function (UserInterface $user) {
      return $user->label();
    }, $users);
    natsort($user_options);

    // Operator - select - required.
    $element = [
      '#type' => 'select',
      '#title' => $this->t('Operator'),
      '#options' => $user_options,
      '#required' => TRUE,
      '#weight' => $weight,
    ];

    return $element;
  }

}
