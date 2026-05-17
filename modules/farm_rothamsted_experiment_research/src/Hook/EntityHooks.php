<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_experiment_research\Hook;

use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\entity\BundleFieldDefinition;

/**
 * Entity hook implementations for farm_rothamsted_experiment_research.
 */
class EntityHooks {

  use AutowireTrait;
  use StringTranslationTrait;

  public function __construct(
    protected AccountProxyInterface $currentUser,
    protected MessengerInterface $messenger,
  ) {}

  /**
   * Implements hook_farm_entity_bundle_field_info().
   */
  #[Hook('farm_entity_bundle_field_info')]
  public function farmEntityBundleFieldInfo(EntityTypeInterface $entity_type, string $bundle): array {
    $fields = [];

    // Add a design reference field to experiment plans.
    if ($entity_type->id() == 'plan' && $bundle == 'rothamsted_experiment') {

      // Reference to the experiment design.
      $fields['experiment_design'] = BundleFieldDefinition::create('entity_reference')
        ->setLabel($this->t('Experiment Design'))
        ->setRevisionable(TRUE)
        ->setRequired(TRUE)
        ->setSetting('target_type', 'rothamsted_design')
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayOptions('form', [
          'type' => 'entity_reference_autocomplete',
          'settings' => [
            'match_operator' => 'CONTAINS',
            'size' => 60,
            'placeholder' => '',
          ],
          'weight' => -100,
        ])
        ->setDisplayConfigurable('view', TRUE)
        ->setDisplayOptions('view', [
          'label' => 'inline',
          'type' => 'entity_reference_label',
          'weight' => -100,
        ]);
    }

    return $fields;
  }

  /**
   * Implements hook_entity_form_display_alter().
   */
  #[Hook('entity_form_display_alter')]
  public function entityFormDisplayAlter(EntityFormDisplayInterface $form_display, array $context): void {
    $comment_bundles = [
      'rothamsted_proposal',
      'rothamsted_program',
      'rothamsted_experiment',
      'rothamsted_design',
    ];
    if ($form_display->getTargetEntityTypeId() == 'comment' && in_array($context['bundle'], $comment_bundles) && $form_display->getMode() == 'default' && $form_display->isNew()) {
      $form_display->setComponent('author', [
        'region' => 'content',
        'settings' => [],
        'weight' => 0,
      ]);
      $form_display->setComponent('comment_body', [
        'type' => 'text_textarea',
        'region' => 'content',
        'settings' => [
          'rows' => 5,
          'placeholder' => '',
        ],
        'weight' => 1,
      ]);
      $form_display->removeComponent('subject');

      // Alter proposal comments.
      if ($context['bundle'] == 'rothamsted_proposal') {
        $form_display->setComponent('proposal_review', [
          'type' => 'boolean_checkbox',
          'region'   => 'content',
          'settings' => [
            'display_label' => TRUE,
          ],
          'weight'   => 2,
        ]);
      }
    }
  }

  /**
   * Implements hook_entity_view_display_alter().
   */
  #[Hook('entity_view_display_alter')]
  public function entityViewDisplayAlter(EntityViewDisplayInterface $display, array $context): void {
    $comment_bundles = [
      'rothamsted_proposal',
      'rothamsted_program',
      'rothamsted_experiment',
      'rothamsted_design',
    ];
    $display_modes = ['default', 'full'];
    if ($context['entity_type'] == 'comment' && in_array($context['bundle'], $comment_bundles) && in_array($display->getMode(), $display_modes) && $display->isNew()) {
      $display->setComponent('comment_body', [
        'type' => 'text_default',
        'label' => 'hidden',
        'region' => 'content',
        'settings' => [],
        'weight' => 0,
      ]);
      $display->setComponent('links', [
        'region' => 'content',
        'settings' => [],
        'weight' => 1,
      ]);

      // Alter proposal comments.
      if ($context['bundle'] == 'rothamsted_proposal') {
        $display->setComponent('proposal_review', [
          'type' => 'hideable_boolean',
          'label' => 'inline',
          'region'   => 'content',
          'settings' => [
            'format' => 'default',
            'hide_if_false' => TRUE,
            'hide_if_true' => FALSE,
          ],
          'weight'   => 1,
        ]);
      }
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_view().
   */
  #[Hook('rothamsted_proposal_view')]
  public function rothamstedProposalView(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, string $view_mode): void {
    if (!$entity) {
      return;
    }
    // Display link to submit the proposal.
    $url = Url::fromRoute('farm_rothamsted_experiment_research.proposal.submit_form', ['rothamsted_proposal' => $entity->id()], ['query' => ['destination' => $entity->toUrl()->toString()]]);
    if ($url->access($this->currentUser)) {
      $this->messenger->addWarning($this->t('This proposal is currently in a draft state. Click here to <a href="@url">submit proposal</a>', ['@url' => $url->setAbsolute()->toString()]));
    }
  }

}
