<?php

/**
 * @file
 * Update hooks for farm_rothamsted.module.
 */

use Drupal\views\Entity\View;
use Symfony\Component\Yaml\Yaml;

/**
 * Create rothamsted_quick_logs view.
 */
function farm_rothamsted_quick_post_update_create_rothamsted_quick_logs_view(&$sandbox = NULL) {

  // Only create the view if views is enabled.
  $view_id = 'rothamsted_quick_logs';
  if (\Drupal::moduleHandler()->moduleExists('views') && !View::load($view_id)) {
    $config_path = \Drupal::service('extension.list.module')->getPath('farm_rothamsted_quick') . "/config/install/views.view.$view_id.yml";
    $data = Yaml::parseFile($config_path);
    \Drupal::configFactory()->getEditable("views.view.$view_id")->setData($data)->save(TRUE);
  }
}
