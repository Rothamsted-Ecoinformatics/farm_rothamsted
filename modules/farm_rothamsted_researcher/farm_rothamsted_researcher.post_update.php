<?php

/**
 * @file
 * Update hooks for farm_rothamsted.module.
 */

use Drupal\views\Entity\View;
use Symfony\Component\Yaml\Yaml;

/**
 * Rothamsted proposal field changes.
 */
function farm_rothamsted_researcher_post_update_2_12_create_researcher_reference_view(&$sandbox = NULL) {
  // Only create the view if views is enabled.
  $view_id = 'rothamsted_researcher_reference';
  if (\Drupal::moduleHandler()->moduleExists('views') && !View::load($view_id)) {
    $config_path = \Drupal::service('extension.list.module')->getPath('farm_rothamsted_researcher') . "/config/optional/views.view.$view_id.yml";
    $data = Yaml::parseFile($config_path);
    \Drupal::configFactory()->getEditable("views.view.$view_id")->setData($data)->save(TRUE);
  }
}

/**
 * Remove Orcid URL prefixes from orcid field values.
 */
function farm_rothamsted_researcher_post_update_2_14_remove_orcid_prefix(&$sandbox = NULL) {
  foreach (['rothamsted_researcher_data', 'rothamsted_researcher_field_revision'] as $table) {
    \Drupal::database()->update($table)
      ->expression('orcid', "REPLACE(orcid, 'https://orcid.org/', '')")
      ->condition('orcid', 'https://orcid.org/%', 'LIKE')
      ->execute();
  }
}
