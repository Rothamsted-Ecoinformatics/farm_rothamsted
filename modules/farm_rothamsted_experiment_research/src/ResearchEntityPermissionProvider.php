<?php

namespace Drupal\farm_rothamsted_experiment_research;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\UncacheableEntityPermissionProvider;

/**
 * Permission provider for research entities.
 */
class ResearchEntityPermissionProvider extends UncacheableEntityPermissionProvider {

  /**
   * {@inheritdoc}
   */
  public function buildEntityTypePermissions(EntityTypeInterface $entity_type) {
    $permissions = parent::buildEntityTypePermissions($entity_type);
    $research_permission_provider = \Drupal::classResolver(ResearchPermissionProvider::class);
    $permissions += $research_permission_provider->buildPermissions($entity_type);
    return $permissions;
  }

}
