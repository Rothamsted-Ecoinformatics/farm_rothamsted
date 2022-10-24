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

/**
 * Create rothamsted_quick_location_reference view.
 */
function farm_rothamsted_quick_post_update_create_rothamsted_quick_location_reference_view(&$sandbox = NULL) {

  // Only create the view if views is enabled.
  $view_id = 'rothamsted_quick_location_reference';
  if (\Drupal::moduleHandler()->moduleExists('views') && !View::load($view_id)) {
    $config_path = \Drupal::service('extension.list.module')->getPath('farm_rothamsted_quick') . "/config/install/views.view.$view_id.yml";
    $data = Yaml::parseFile($config_path);
    \Drupal::configFactory()->getEditable("views.view.$view_id")->setData($data)->save(TRUE);
  }
}

/**
 * Update previous quick form submissions to get correct log categories.
 */
function farm_rothamsted_quick_post_update_log_categories_288(&$sandbox = NULL) {

  $log_storage = \Drupal::entityTypeManager()->getStorage('log');
  $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

  $quick_notes_query_mapping = [
    'trailer_harvest' => 'Type of harvest:',
    'field_operations' => 'Task:',
  ];
  foreach ($quick_notes_query_mapping as $quick_id => $notes_query) {

    // Query logs submitted for the quick form that contain the notes value.
    $logs = $log_storage->getQuery()
      ->condition('quick', $quick_id)
      ->condition('notes.value', $notes_query, 'CONTAINS')
      ->execute();

    // Iterate over all matching logs.
    foreach ($log_storage->loadMultiple($logs) as $log) {

      // Use a regex to find the accompanying notes value.
      $exp = "/($notes_query)(.*)/";
      $matches = [];
      $notes_value = $log->get('notes')->value;
      preg_match($exp, $notes_value, $matches);

      // If a match is found update the log category.
      if (count($matches) == 3) {

        // Search for the log_category term name.
        $term_name = trim($matches[2]);
        $matching_terms = $term_storage->loadByProperties([
          'vid' => 'log_category',
          'status' => 1,
          'name' => $term_name,
        ]);

        // If there is a matching term update the log category.
        if ($matching_term = reset($matching_terms)) {

          /** @var \Drupal\Core\Field\FieldItemListInterface $log_category_field */
          $log_category_field = $log->get('category');
          $log_category_field->appendItem($matching_term);
          $log->save();

          // Log message.
          $log_id = $log->id();
          $log_message = "Updated log $log_id category: '$term_name'";
          \Drupal::logger('farm_rothamsted_quick')->info($log_message);
        }
      }
    }
  }
}
