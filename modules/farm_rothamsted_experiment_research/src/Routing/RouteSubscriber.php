<?php

namespace Drupal\farm_rothamsted_experiment_research\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Alter routes for the experiment research module.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {

    // view.farm_rothamsted_experiment_plan.page_research.
    if ($route = $collection->get('view.rothamsted_experiment_plan.page_research')) {
      // Set default status to mark primary tab as active.
      $route->setDefault('status', 'all');
    }
  }

}
