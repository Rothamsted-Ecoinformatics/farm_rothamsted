<?php

namespace Drupal\farm_rothamsted\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Alter routes for the farm_rothamsted module.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {

    // Only display the "Plots" tab for rothamsted_experiment plans.
    // view.plan_plots.page.
    if ($route = $collection->get('view.plan_plots.page')) {
      // Add to existing parameters, if any.
      $parameters = $route->getOption('parameters');
      $parameters['plan'] = [
        'type' => 'entity:plan',
      ];
      $route->setOption('parameters', $parameters);
      // Limit to the rothamsted_experiment bundle.
      $route->setRequirement('_entity_bundles', 'plan:rothamsted_experiment');
    }
  }

}
