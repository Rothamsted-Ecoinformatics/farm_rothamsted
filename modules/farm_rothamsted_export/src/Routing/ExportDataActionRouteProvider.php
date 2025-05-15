<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_export\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides routes for the entity CSV export action.
 */
class ExportDataActionRouteProvider implements EntityRouteProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = new RouteCollection();
    $entity_type_id = $entity_type->id();
    if ($route = $this->getEntityCsvFormRoute($entity_type)) {
      $collection->add("entity.$entity_type_id.export_data_action_form", $route);
    }

    return $collection;
  }

  /**
   * Gets the entity CSV export form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getEntityCsvFormRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('export-data-action-form')) {
      $route = new Route($entity_type->getLinkTemplate('export-data-action-form'));
      $route->setDefault('_form', $entity_type->getFormClass('export-data-action-form'));
      $route->setDefault('entity_type', $entity_type->id());
      $route->setRequirement('_user_is_logged_in', 'TRUE');
      return $route;
    }
    return NULL;
  }

}
