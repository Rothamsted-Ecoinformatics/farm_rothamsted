<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_experiment_research\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\comment\CommentInterface;
use Drupal\entity\BundleFieldDefinition;
use Drupal\farm_rothamsted_experiment_research\ResearchNotificationHandler;

/**
 * Entity hook implementations for farm_rothamsted_experiment_research.
 */
class EntityHooks {

  use StringTranslationTrait;

  public function __construct(
    protected AccountProxyInterface $currentUser,
    protected MessengerInterface $messenger,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ClassResolverInterface $classResolver,
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

    // Add experiment_deviation field to logs.
    if ($entity_type->id() === 'log') {
      $fields['experiment_deviation'] = BundleFieldDefinition::create('text_long')
        ->setLabel(new TranslatableMarkup('Experiment Deviations'))
        ->setDescription(new TranslatableMarkup('Please describe any deviations from the experiment plan or observations that might affect the outcome of the experiment.'))
        ->setRevisionable(TRUE)
        ->setDisplayOptions('form', [
          'type' => 'text_textarea',
          'settings' => [
            'rows' => '5',
            'placeholder' => '',
          ],
          'weight' => 90,
        ])
        ->setDisplayOptions('view', [
          'label' => 'inline',
          'type' => 'text_default',
          'weight' => 90,
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

  /**
   * Implements hook_ENTITY_TYPE_insert().
   */
  #[Hook('comment_insert')]
  public function commentInsert(EntityInterface $entity): void {
    if ($entity->bundle() == 'rothamsted_proposal' && $entity->get('proposal_review')->value) {
      $this->updateProposalReviewer($entity);
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_update().
   */
  #[Hook('comment_update')]
  public function commentUpdate(EntityInterface $entity): void {
    if ($entity->bundle() == 'rothamsted_proposal' && $entity->get('proposal_review')->value) {
      $this->updateProposalReviewer($entity);
    }
  }

  /**
   * Implements hook_entity_field_access().
   */
  #[Hook('entity_field_access')]
  public function entityFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, ?FieldItemListInterface $items = NULL) {
    if ($operation == 'edit' && $field_definition->getTargetEntityTypeId() == 'comment' && $field_definition->getName() == 'proposal_review') {
      return AccessResult::forbiddenIf(!$account->hasPermission('review rothamsted_proposal'));
    }
    return AccessResult::neutral();
  }

  /**
   * Implements hook_entity_access().
   */
  #[Hook('entity_access')]
  public function entityAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $research_entity_types = [
      'asset',
      'log',
      'quantity',
      'rothamsted_proposal',
      'rothamsted_program',
      'rothamsted_experiment',
      'rothamsted_design',
    ];
    if (!in_array($entity->getEntityTypeId(), $research_entity_types)) {
      return AccessResult::neutral();
    }

    // Only check view, update and delete operations.
    if (!in_array($operation, ['view', 'update', 'delete'])) {
      return AccessResult::neutral();
    }

    // Delegate to helper.
    return $this->researchEntityAccess($entity, $operation, $account);
  }

  /**
   * Implements hook_ENTITY_TYPE_access().
   */
  #[Hook('plan_access')]
  public function planAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    // Only check experiment plans.
    if ($entity->bundle() != 'rothamsted_experiment') {
      return AccessResult::neutral();
    }

    // Only check view, update and delete operations.
    if (!in_array($operation, ['view', 'update', 'delete'])) {
      return AccessResult::neutral();
    }

    // Delegate to helper.
    return $this->researchEntityAccess($entity, $operation, $account);
  }

  /**
   * Implements hook_entity_insert().
   */
  #[Hook('entity_insert')]
  public function entityInsert(EntityInterface $entity): void {

    // Only send emails for these entity types.
    $core_entity_type_ids = [
      'comment',
      'log',
      'plan',
    ];
    $rothamsted_entity_type_ids = [
      'plan',
      'rothamsted_design',
      'rothamsted_experiment',
      'rothamsted_program',
      'rothamsted_proposal',
      'rothamsted_researcher',
    ];
    $entity_type_id = $entity->getEntityTypeId();
    if (!in_array($entity_type_id, $rothamsted_entity_type_ids) && !in_array($entity_type_id, $core_entity_type_ids)) {
      return;
    }

    // Only send emails for comments about these entity types.
    if ($entity_type_id == 'comment' && !in_array($entity->bundle(), $rothamsted_entity_type_ids)) {
      return;
    }

    /** @var \Drupal\farm_rothamsted_experiment_research\ResearchNotificationHandler $notification_handler */
    $notification_handler = $this->classResolver->getInstanceFromDefinition(ResearchNotificationHandler::class);

    // Add entity type logic.
    switch ($entity_type_id) {
      case 'rothamsted_researcher':
      case 'rothamsted_program':
      case 'rothamsted_experiment':
      case 'rothamsted_design':
        $notification_handler->buildNewEntityAlert($entity);
        return;

      case 'rothamsted_proposal':
        $notification_handler->buildNewEntityAlert($entity);
        return;

      case 'plan':
        if ($entity->bundle() == 'rothamsted_experiment') {
          $notification_handler->buildNewEntityAlert($entity);
        }
        return;

      case 'comment':
        $notification_handler->buildNewCommentAlert($entity);
        return;

      case 'log':
        $notification_handler->buildNewLogAlert($entity);
        return;
    }
  }

  /**
   * Implements hook_entity_update().
   */
  #[Hook('entity_update')]
  public function entityUpdate(EntityInterface $entity): void {

    // Only send emails for these entity types.
    $rothamsted_entity_type_ids = [
      'log',
      'plan',
      'rothamsted_design',
      'rothamsted_experiment',
      'rothamsted_program',
      'rothamsted_proposal',
      'rothamsted_researcher',
    ];
    $entity_type_id = $entity->getEntityTypeId();
    if (!in_array($entity_type_id, $rothamsted_entity_type_ids)) {
      return;
    }

    /** @var \Drupal\farm_rothamsted_experiment_research\ResearchNotificationHandler $notification_handler */
    $notification_handler = $this->classResolver->getInstanceFromDefinition(ResearchNotificationHandler::class);

    // Add entity type logic.
    // Send new entity alert emails to notify if new researchers were added
    // and also send updated entity alerts for all users.
    switch ($entity_type_id) {
      case 'rothamsted_researcher':
      case 'rothamsted_proposal':
      case 'rothamsted_program':
      case 'rothamsted_experiment':
      case 'rothamsted_design':
        $notification_handler->buildNewEntityAlert($entity, TRUE);
        $notification_handler->buildUpdatedEntityAlert($entity);
        return;

      case 'plan':
        if ($entity->bundle() == 'rothamsted_experiment') {
          $notification_handler->buildUpdatedEntityAlert($entity);
        }
        return;

      case 'log':
        $notification_handler->buildUpdatedLogAlert($entity);
        return;
    }
  }

  /**
   * Helper to update proposal reviewers for a comment.
   */
  protected function updateProposalReviewer(CommentInterface $comment): void {

    // Get matching researcher.
    $researcher = $this->entityTypeManager->getStorage('rothamsted_researcher')->loadByProperties([
      'farm_user' => $comment->getOwnerId(),
    ]);
    if (empty($researcher)) {
      return;
    }

    // Update the proposal.reviewer field.
    $proposal = $comment->getCommentedEntity();
    $reviewer_ids = array_column($proposal->get('reviewer')->getValue(), 'target_id');
    $reviewer_ids[] = reset($researcher)->id();
    $proposal->set('reviewer', array_unique($reviewer_ids));
    $proposal->save();
  }

  /**
   * Helper to determine account access to research entities.
   *
   * Access is granted if accounts are the experiment admin or if accounts are
   * associated with a researcher that is assigned to the experiment atop
   * the research hierarchy.
   */
  protected function researchEntityAccess(EntityInterface $entity, string $operation, AccountInterface $account): AccessResult {

    // Allow access if the user is an experiment admin.
    if ($account->hasPermission('administer rothamsted_experiment plan')) {
      return AccessResult::allowedIfHasPermission($account, 'administer rothamsted_experiment plan');
    }

    // Add cache tags to all other access results. This ensures that secondary
    // task items are rebuilt on the entity page when other entities change.
    // For example, if a researcher is removed from an Experiment entity,
    // the plan should be updated to not have the "edit" tab. Without these
    // cache tags the previous result can be cached.
    $research_entity_cache_tags = [
      'rothamsted_researcher_list',
      'rothamsted_proposal_list',
      'rothamsted_program_list',
      'rothamsted_experiment_list',
      'rothamsted_design_list',
      'plan_list:rothamsted_experiment',
    ];

    // Build the research_assigned permission string.
    $bundle_permissions = $entity->getEntityType()->getPermissionGranularity() == 'bundle';
    $assigned_research_permission = $bundle_permissions ? "$operation research_assigned {$entity->bundle()} {$entity->getEntityTypeId()}" : "$operation research_assigned {$entity->getEntityTypeId()}";

    // Only check access if the user has research_assigned permission.
    if (!$account->hasPermission($assigned_research_permission)) {
      return AccessResult::neutral()->addCacheTags($research_entity_cache_tags);
    }

    // Logic based on entity type.
    switch ($entity->getEntityTypeId()) {

      case 'quantity':
        // Get the log that references the quantity.
        $logs = $this->entityTypeManager->getStorage('log')->loadByProperties([
          'quantity' => $entity->id(),
        ]);

        // Bail if there are no logs.
        if (empty($logs)) {
          return AccessResult::neutral();
        }

        // Delegate to log access logic.
        return $this->researchEntityAccess(reset($logs), $operation, $account);

      case 'log':
        // Collect asset IDs the log references.
        $asset_ids = array_column($entity->get('asset')->getValue(), 'target_id');
        $location_ids = array_column($entity->get('location')->getValue(), 'target_id');
        $log_asset_ids = array_merge($asset_ids, $location_ids);

        // Bail if log does not reference any asset.
        if (empty($log_asset_ids)) {
          return AccessResult::forbidden();
        }

        // Find the plan associated with the asset.
        $query = $this->entityTypeManager->getStorage('plan')->getQuery()
          ->accessCheck(FALSE)
          ->condition('type', 'rothamsted_experiment')
          ->condition('experiment_design.entity:rothamsted_design.experiment.entity:rothamsted_experiment.researcher.entity:rothamsted_researcher.farm_user', $account->id());
        $asset_reference = $query->orConditionGroup()
          ->condition('plot', $log_asset_ids, 'IN')
          ->condition('asset', $log_asset_ids, 'IN');
        $query->condition($asset_reference);
        $plan_count = $query->count()->execute();
        return AccessResult::allowedIf($plan_count !== 0)->addCacheTags($research_entity_cache_tags);

      case 'asset':
        // Find the plan associated with the plot.
        $query = $this->entityTypeManager->getStorage('plan')->getQuery()
          ->accessCheck(FALSE)
          ->condition('type', 'rothamsted_experiment')
          ->condition('experiment_design.entity:rothamsted_design.experiment.entity:rothamsted_experiment.researcher.entity:rothamsted_researcher.farm_user', $account->id());
        $asset_reference = $query->orConditionGroup()
          ->condition('plot', $entity->id())
          ->condition('asset', $entity->id());
        $query->condition($asset_reference);
        $plan_count = $query->count()->execute();
        return AccessResult::allowedIf($plan_count !== 0)->addCacheTags($research_entity_cache_tags);

      case 'rothamsted_proposal':
        // For proposals check for contact, statistician or data steward.
        $query = $this->entityTypeManager->getStorage('rothamsted_proposal')->getQuery()
          ->accessCheck(FALSE)
          ->condition('id', $entity->id());
        $user_reference = $query->orConditionGroup()
          ->condition('contact.entity:rothamsted_researcher.farm_user', $account->id())
          ->condition('statistician.entity:rothamsted_researcher.farm_user', $account->id())
          ->condition('data_steward.entity:rothamsted_researcher.farm_user', $account->id());
        $query->condition($user_reference);
        $proposal_count = $query->count()->execute();
        return AccessResult::allowedIf($proposal_count !== 0)->addCacheTags($research_entity_cache_tags);

      case 'rothamsted_program':
        // For programs, check for a matching principal investigator.
        $program_count = $this->entityTypeManager->getStorage('rothamsted_program')->getQuery()
          ->accessCheck(FALSE)
          ->condition('id', $entity->id())
          ->condition('principal_investigator.entity:rothamsted_researcher.farm_user', $account->id())
          ->count()
          ->execute();
        return AccessResult::allowedIf($program_count !== 0)->addCacheTags($research_entity_cache_tags);

      case 'rothamsted_experiment':
        // For experiments, check for a matching researcher.
        $experiment_count = $this->entityTypeManager->getStorage('rothamsted_experiment')->getQuery()
          ->accessCheck(FALSE)
          ->condition('id', $entity->id())
          ->condition('researcher.entity:rothamsted_researcher.farm_user', $account->id())
          ->count()
          ->execute();
        return AccessResult::allowedIf($experiment_count !== 0)->addCacheTags($research_entity_cache_tags);

      case 'rothamsted_design':
        // For designs, check the experiment.
        $query = $this->entityTypeManager->getStorage('rothamsted_design')->getQuery()
          ->accessCheck(FALSE)
          ->condition('id', $entity->id());
        $user_reference = $query->orConditionGroup()
          ->condition('statistician.entity:rothamsted_researcher.farm_user', $account->id())
          ->condition('experiment.entity:rothamsted_experiment.researcher.entity:rothamsted_researcher.farm_user', $account->id());
        $query->condition($user_reference);
        $design_count = $query->count()->execute();
        return AccessResult::allowedIf($design_count !== 0)->addCacheTags($research_entity_cache_tags);

      case 'plan':
        // For plans, check the design.
        $plan_count = $this->entityTypeManager->getStorage('plan')->getQuery()
          ->accessCheck(FALSE)
          ->condition('id', $entity->id())
          ->condition('experiment_design.entity:rothamsted_design.experiment.entity:rothamsted_experiment.researcher.entity:rothamsted_researcher.farm_user', $account->id())
          ->count()
          ->execute();
        return AccessResult::allowedIf($plan_count !== 0)->addCacheTags($research_entity_cache_tags);

      default:
        return AccessResult::neutral()->addCacheTags($research_entity_cache_tags);
    }

  }

}
