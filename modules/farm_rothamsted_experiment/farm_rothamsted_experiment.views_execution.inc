<?php

/**
 * @file
 * Provides Views runtime hooks for farm_rothamsted.module.
 */

use Drupal\plan\Entity\Plan;
use Drupal\views\ViewExecutable;

/**
 * Implements hook_views_pre_view().
 */
function farm_rothamsted_experiment_views_pre_view(ViewExecutable $view) {

  // Bail if not the plan_plots view.
  if ($view->id() !== 'plan_plots' || $view->current_display !== 'page') {
    return;
  }

  // Bail if there is no plan argument.
  if (empty($view->args)) {
    return;
  }

  $plan = Plan::load($view->args[0]);
  if (!empty($plan) && $plan->hasField('treatment_factors') && !$plan->get('treatment_factors')->isEmpty()) {

    // Load field factors from json.
    $treatment_factors = json_decode($plan->get('treatment_factors')->value);

    // Build factor options from each factor type.
    $factor_type_options = [];
    foreach ($treatment_factors as $index => $factor_type) {
      $factor_type_options[$index] = $factor_type->name;
    }

    // Alter each "treatment_factor_x" field label to have correct field factor.
    // These labels are used in the table header.
    /** @var \Drupal\views\Plugin\views\field\FieldHandlerInterface[] $fields */
    $fields = $view->getHandlers('field');
    foreach ($fields as $index => $field) {
      if ($field['table'] === 'asset__treatment_factors' && $field['delta_limit'] === 1 && isset($factor_type_options[$field['delta_offset']])) {
        $fields[$index]['label'] = $factor_type_options[$field['delta_offset']];
      }
    }

    // Update the field handlers for the display.
    $view->getDisplay()->setOption('fields', $fields);
  }
}

/**
 * Implements hook_views_pre_render().
 */
function farm_rothamsted_experiment_views_pre_render(ViewExecutable $view) {

  // Bail if not the plan_plots view.
  if ($view->id() !== 'plan_plots' || $view->current_display !== 'page') {
    return;
  }

  // Build the map render array.
  $map = [
    '#type' => 'farm_map',
    '#map_type' => 'farm_rothamsted_experiment_plots',
  ];
  $map['#map_settings']['farm_rothamsted_experiment_plot_layer'] = [
    'plan' => \Drupal::routeMatch()->getRawParameter('plan'),
    'filters' => $view->getExposedInput(),
  ];

  // Render the map.
  $view->attachment_before['asset_map'] = $map;
}
