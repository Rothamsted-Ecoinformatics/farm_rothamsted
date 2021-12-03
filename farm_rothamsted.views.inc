<?php

/**
 * @file
 * Provides Views data for farm_rothamsted.module.
 */

/**
 * Implements hook_views_data_alter().
 */
function farm_rothamsted_views_data_alter(array &$data) {

  // Use the factor_levels filter for the plot field_factors_value.
  if (isset($data['asset__field_factors']['field_factors_value'])) {
    $data['asset__field_factors']['field_factors_value']['filter'] = [
      'id' => 'factor_levels',
    ];
  }
}
