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
  if ($view->id() !== 'rothamsted_experiment_plan_plots' || $view->current_display !== 'page') {
    return;
  }

  // Bail if there is no plan argument.
  if (empty($view->args)) {
    return;
  }

  $plan = Plan::load($view->args[0]);
  if (!empty($plan) && $plan->hasField('column_descriptors') && !$plan->get('column_descriptors')->isEmpty()) {

    // Load column_descriptors from json.
    $column_descriptors = json_decode($plan->get('column_descriptors')->value);

    /** @var \Drupal\views\Plugin\views\field\FieldHandlerInterface[] $fields */
    $fields = $view->getHandlers('field');
    foreach ($column_descriptors as $column) {

      // Build factor options from each factor type.
      $column_levels = [];
      foreach ($column->column_levels as $index => $column_level) {
        $column_levels[$index] = $column_level->level_name;
      }

      // Add a views field for each column.
      $column_id = $column->column_id;
      $field_id = "column_descriptor_$column_id";
      $fields[$field_id] = [
        'id' => $field_id,
        'table' => 'asset__column_descriptors',
        'field' => 'column_descriptors_value',
        'relationship' => 'none',
        'entity_type' => 'asset',
        'entity_field' => 'column_descriptors',
        'plugin_id' => 'field',
        'label' => $column->column_name,
        'type' => 'plot_column_descriptor',
        'settings' => [
          'column_levels' => $column_levels,
          'column_id' => $column_id,
          'raw' => FALSE,
          'value_only' => TRUE,
        ],
      ];
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
  if ($view->id() !== 'rothamsted_experiment_plan_plots' || $view->current_display !== 'page') {
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

  // Add "All locations" layer.
  $map['#map_settings']['asset_type_layers']['all_locations'] = [
    'label' => t('All locations'),
    'filters' => [
      'is_location' => 1,
    ],
    'color' => 'grey',
  ];

  // Render the map.
  $view->attachment_before['asset_map'] = $map;
}
