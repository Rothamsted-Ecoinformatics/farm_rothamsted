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
    $entity_type_id = $entity_type->id();
    $plural_label = $entity_type->getPluralLabel();
    $permissions["view research_assigned {$entity_type_id}"] = [
      'title' => $this->t('View research-assigned @type', [
        '@type' => $plural_label,
      ]),
    ];
    $permissions["update research_assigned {$entity_type_id}"] = [
      'title' => $this->t('Update research-assigned @type', [
        '@type' => $plural_label,
      ]),
    ];
    return $permissions;
  }

}
