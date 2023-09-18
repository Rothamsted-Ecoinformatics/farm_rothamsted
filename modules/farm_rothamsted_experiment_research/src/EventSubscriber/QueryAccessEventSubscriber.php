<?php

namespace Drupal\farm_rothamsted_experiment_research\EventSubscriber;

use Drupal\entity\QueryAccess\ConditionGroup;
use Drupal\entity\QueryAccess\QueryAccessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 *
 */
class QueryAccessEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
    // 'entity.query_access' => 'onEntityQuery',
      'entity.query_access.rothamsted_design' => 'onDesignQuery',
      'entity.query_access.rothamsted_researcher' => 'onResearcherQuery',
      'entity.query_access.asset' => 'onAssetQuery',
    ];
  }

  /**
   * Trigger notifications when data is received.
   *
   * @param \Drupal\entity\QueryAccess\QueryAccessEvent $event
   *   The data stream event.
   */
  public function onEntityQuery(QueryAccessEvent $event) {
    $x = 1;
    $conditions = $this->buildEntityOwnerConditions($event->getEntityTypeId(), $event->getOperation(), $event->getAccount());
    $event->getConditions()->addCondition($conditions);
  }

  /**
   * Trigger notifications when data is received.
   *
   * @param \Drupal\entity\QueryAccess\QueryAccessEvent $event
   *   The data stream event.
   */
  public function onAssetQuery(QueryAccessEvent $event) {
    $entity_type_id = $event->getEntityTypeId();
    $operation = $event->getOperation();
    $account = $event->getAccount();
    $conditions = $event->getConditions();
    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);

    // Any $entity_type permission.
    if ($account->hasPermission("$operation any $entity_type_id")) {
      // The user has full access, no conditions needed.
      $conditions = new ConditionGroup('OR');
      $conditions->addCacheContexts(['user.permissions']);
      return $conditions;
    }

    // Research_assigned $entity_type permission.
    if ($account->hasPermission("$operation assigned $entity_type_id")) {
      $conditions->addCacheContexts(['user']);
      $conditions->addCondition('farm_user', $account->id());
    }

    /** @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info */
    $entity_type_bundle_info = \Drupal::service('entity_type.bundle.info');
    $bundles = array_keys($entity_type_bundle_info->getBundleInfo($entity_type_id));

    $bundles_with_any_permission = [];
    $bundles_with_research_permission = [];
    foreach ($bundles as $bundle) {
      if ($account->hasPermission("$operation any $bundle $entity_type_id")) {
        $bundles_with_any_permission[] = $bundle;
      }
      if ($account->hasPermission("$operation research_assigned $bundle $entity_type_id")) {
        $bundles_with_research_permission[] = $bundle;
      }
    }
    // Any $bundle permission.
    if ($bundles_with_any_permission) {
      $conditions->addCondition('type', $bundles_with_any_permission);
    }

    // Research_assigned $bundle permission.
    if ($bundles_with_research_permission) {
      $conditions->addCacheContexts(['user']);
      $conditions->addCondition((new ConditionGroup('AND'))
        // @todo This is not correct.
        ->addCondition('uid', $account->id())
        ->addCondition('type', $bundles_with_research_permission)
      );
    }

    return $conditions->count() ? $conditions : NULL;
  }

  /**
   * Trigger notifications when data is received.
   *
   * @param \Drupal\entity\QueryAccess\QueryAccessEvent $event
   *   The data stream event.
   */
  public function onDesignQuery(QueryAccessEvent $event) {
    $entity_type_id = $event->getEntityTypeId();
    $operation = $event->getOperation();
    $account = $event->getAccount();
    $conditions = $event->getConditions();

    // Any $entity_type permission.
    if ($account->hasPermission("$operation any $entity_type_id")) {
      // The user has full access, no conditions needed.
      $conditions = new ConditionGroup('OR');
      $conditions->addCacheContexts(['user.permissions']);
      return $conditions;
    }

    $conditions = new ConditionGroup('AND');
    $conditions->addCacheContexts(['user.permissions']);

    // Research_assigned $entity_type permission.
    if ($account->hasPermission("$operation research_assigned $entity_type_id")) {
      $conditions->addCacheContexts(['user']);
      $conditions->addCondition('experiment.entity:rothamsted_experiment.researcher.entity:rothamsted_researcher.farm_user', $account->id());
    }

    if ($conditions->count()) {
      $event->getConditions()->addCondition($conditions);
    }
  }

  /**
   * Trigger notifications when data is received.
   *
   * @param \Drupal\entity\QueryAccess\QueryAccessEvent $event
   *   The data stream event.
   */
  public function onResearcherQuery(QueryAccessEvent $event) {
    $entity_type_id = $event->getEntityTypeId();
    $operation = $event->getOperation();
    $account = $event->getAccount();
    $conditions = $event->getConditions();

    // Any $entity_type permission.
    if ($account->hasPermission("$operation any $entity_type_id")) {
      // The user has full access, no conditions needed.
      $conditions = new ConditionGroup('OR');
      $conditions->addCacheContexts(['user.permissions']);
      return $conditions;
    }

    $conditions = new ConditionGroup('AND');
    $conditions->addCacheContexts(['user.permissions']);

    // Research_assigned $entity_type permission.
    if ($account->hasPermission("$operation assigned $entity_type_id")) {
      $conditions->addCacheContexts(['user']);
      $conditions->addCondition('farm_user', $account->id());
    }

    if ($conditions->count()) {
      $event->getConditions()->addCondition($conditions);
    }
  }

}
