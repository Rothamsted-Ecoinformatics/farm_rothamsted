<?php

namespace Drupal\farm_rothamsted_experiment\Routing;

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

    // Only display the plan views for rothamsted_experiment plans.
    foreach (['view.plan_plots.page', 'view.rothamsted_experiment_plan_logs.page'] as $view_id) {
      if ($route = $collection->get($view_id)) {
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

}
