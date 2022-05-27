<?php

/**
 * @file
 * Provides Views data for farm_rothamsted_experiment.module.
 */

/**
 * Implements hook_views_data_alter().
 */
function farm_rothamsted_experiment_views_data_alter(array &$data) {

  // Use the factor_levels filter for the plot field_factors_value.
  if (isset($data['asset__treatment_factors']['treatment_factors_value'])) {
    $data['asset__treatment_factors']['treatment_factors_value']['filter'] = [
      'id' => 'factor_levels',
    ];
  }

  // Use the plan_plot_types filter for the plot_type filter.
  if (isset($data['asset__plot_type']['plot_type_value'])) {
    $data['asset__plot_type']['plot_type_value']['filter'] = [
      'id' => 'plan_plot_type',
    ];
  }
}
