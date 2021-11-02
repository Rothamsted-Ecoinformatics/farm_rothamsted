<?php

/**
 * @file
 * Provides Views runtime hooks for farm_rothamsted.module.
 */

use Drupal\views\ViewExecutable;

/**
 * Implements hook_views_pre_render().
 */
function farm_rothamsted_views_pre_render(ViewExecutable $view) {

  // Bail if not the plan_plots view.
  if ($view->id() !== 'plan_plots' || $view->current_display !== 'page_1') {
    return;
  }

  // Build the map render array.
  $map = [
    '#type' => 'farm_map',
    '#map_type' => 'farm_rothamsted_experiment_plots',
  ];
  $map['#map_settings']['farm_rothamsted_experiment_plot_layer']['plan'] = \Drupal::routeMatch()->getRawParameter('plan');

  // Render the map.
  $view->attachment_before['asset_map'] = $map;
}
