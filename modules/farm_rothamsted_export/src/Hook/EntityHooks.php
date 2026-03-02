<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_export\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\farm_rothamsted_export\Form\ExportDataActionForm;
use Drupal\farm_rothamsted_export\Routing\ExportDataActionRouteProvider;

/**
 * Entity hook implementations for farm_rothamsted_export.
 */
class EntityHooks {

  /**
   * Implements hook_entity_type_build().
   */
  #[Hook('entity_type_build')]
  public function entityTypeBuild(array &$entity_types): void {

    // Enable the entity export data action on target entity types.
    $target_entity_types = [
      'asset',
      'log',
      'quantity',
      'plan',
      'rothamsted_researcher',
      'rothamsted_design',
      'rothamsted_experiment',
      'rothamsted_program',
      'rothamsted_proposal',
    ];
    foreach ($target_entity_types as $entity_type) {
      if (!empty($entity_types[$entity_type])) {
        $route_providers = $entity_types[$entity_type]->getRouteProviderClasses();
        $route_providers['export-data'] = ExportDataActionRouteProvider::class;
        $entity_types[$entity_type]->setHandlerClass('route_provider', $route_providers);
        $entity_types[$entity_type]->setLinkTemplate('export-data-action-form', "/$entity_type/export");
        $entity_types[$entity_type]->setFormClass('export-data-action-form', ExportDataActionForm::class);
      }
    }
  }

}
