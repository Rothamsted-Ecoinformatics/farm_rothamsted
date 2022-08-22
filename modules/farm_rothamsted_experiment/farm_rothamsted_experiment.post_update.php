<?php

/**
 * @file
 * Update hooks for farm_rothamsted_experiment.module.
 */

use Symfony\Component\Yaml\Yaml;

/**
 * Update farm_rothamsted_experiment_plots map type to include asset layers.
 */
function farm_rothamsted_experiment_post_update_update_experiment_plots_map_type(&$sandbox = NULL) {

  // Update the farm_rothamsted_experiment_plots map type.
  $map_type_id = 'farm_rothamsted_experiment_plots';
  $config_path = \Drupal::service('extension.list.module')->getPath('farm_rothamsted_experiment') . "/config/install/farm_map.map_type.$map_type_id.yml";
  $data = Yaml::parseFile($config_path);
  \Drupal::configFactory()->getEditable("farm_map.map_type.$map_type_id")->setData($data)->save(TRUE);
}
