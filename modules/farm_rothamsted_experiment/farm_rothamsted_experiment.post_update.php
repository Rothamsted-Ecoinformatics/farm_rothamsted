<?php

/**
 * @file
 * Update hooks for farm_rothamsted_experiment.module.
 */

use Symfony\Component\Yaml\Yaml;

/**
 * Create experiment_boundary land type.
 */
function farm_rothamsted_experiment_post_update_create_experiment_boundary_land_type(&$sandbox = NULL) {
  $land_type = 'experiment_boundary';
  $config_path = \Drupal::service('extension.list.module')->getPath('farm_rothamsted_experiment') . "/config/install/farm_land.land_type.$land_type.yml";
  $data = Yaml::parseFile($config_path);
  \Drupal::configFactory()->getEditable("farm_land.land_type.$land_type")->setData($data)->save(TRUE);
}
