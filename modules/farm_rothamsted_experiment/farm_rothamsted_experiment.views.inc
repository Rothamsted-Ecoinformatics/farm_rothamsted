<?php

/**
 * @file
 * Provides Views data for farm_rothamsted_experiment.module.
 */

/**
 * Implements hook_views_data_alter().
 */
function farm_rothamsted_experiment_views_data_alter(array &$data) {

  // Use the column_levels filter for the plot column_descriptors_value.
  if (isset($data['asset__column_descriptors']['column_descriptors_value'])) {
    $data['asset__column_descriptors']['column_descriptors_value']['filter'] = [
      'id' => 'column_level',
    ];
  }

  // Use the plan_plot_types filter for the plot_type filter.
  if (isset($data['asset__plot_type']['plot_type_value'])) {
    $data['asset__plot_type']['plot_type_value']['filter'] = [
      'id' => 'plan_plot_type',
    ];
  }

  // Add a reverse relationship for all experiment plan logs.
  $data['log_field_data']['reverse__rothamsted_experiment_log'] = [
    'title' => t('Experiment plan'),
    'help' => t('Relate each log with the rothamsted experiment plan ALL.'),
    'relationship' => [
      'id' => 'standard',
      'join_id' => 'rothamsted_experiment_logs',
      'base' => 'plan_field_data',
      'base field' => 'id',
      'relationship table' => 'rothamsted_experiment_plan_logs',
      'label' => t('Plan'),
      'group' => t('Log'),
      'entity_type' => 'plan',
    ],
  ];

  // Add a reverse relationship for only experiment plan logs via plan.asset.
  $data['log_field_data']['reverse__rothamsted_experiment_asset_log'] = [
    'title' => t('Experiment plan'),
    'help' => t('Relate each log with the rothamsted experiment plan ASSET.'),
    'relationship' => [
      'id' => 'standard',
      'join_id' => 'rothamsted_experiment_asset_logs',
      'base' => 'plan_field_data',
      'base field' => 'id',
      'relationship table' => 'rothamsted_experiment_plan_asset_logs',
      'label' => t('Plan'),
      'group' => t('Log'),
      'entity_type' => 'plan',
    ],
  ];

  // Add a reverse relationship for only experiment plan logs via plan.plot.
  $data['log_field_data']['reverse__rothamsted_experiment_plot_log'] = [
    'title' => t('Experiment plan'),
    'help' => t('Relate each log with the rothamsted experiment plan PLOT.'),
    'relationship' => [
      'id' => 'standard',
      'join_id' => 'rothamsted_experiment_plot_logs',
      'base' => 'plan_field_data',
      'base field' => 'id',
      'relationship table' => 'rothamsted_experiment_plan_plot_logs',
      'label' => t('Plan'),
      'group' => t('Log'),
      'entity_type' => 'plan',
    ],
  ];
}
