<?php

/**
 * @file
 * Update hooks for farm_rothamsted.module.
 */

use Drupal\quantity\Entity\Quantity;
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
      ->accessCheck(FALSE)
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

/**
 * Copy seed_dressing notes from previous quick form submissions.
 */
function farm_rothamsted_quick_post_update_seed_dressing_notes_2(&$sandbox = NULL) {

  $log_storage = \Drupal::entityTypeManager()->getStorage('log');
  $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

  // Query logs submitted for the quick form that contain the notes value.
  $notes_query = 'Seed dressings:';
  $logs = $log_storage->getQuery()
    ->accessCheck(FALSE)
    ->condition('quick', 'drilling')
    ->condition('notes.value', $notes_query, 'CONTAINS')
    ->execute();

  // Iterate over all matching logs.
  foreach ($log_storage->loadMultiple($logs) as $log) {

    // Use a regex to find the accompanying notes value.
    $exp = "/($notes_query)(.*)/";
    $matches = [];
    $notes_value = $log->get('notes')->value;
    preg_match($exp, $notes_value, $matches);

    // If a match is found query terms and update the log.
    if (count($matches) == 3) {

      // Get array from comma list of seed dressings.
      $notes_dressing_value = trim($matches[2]);
      $term_names = explode(',', $notes_dressing_value) ?? [];

      // Check each term name.
      foreach ($term_names as $term_name) {

        // Search for the material_type term name.
        $term_name = trim($term_name);
        $matching_terms = $term_storage->loadByProperties([
          'vid' => 'material_type',
          'status' => 1,
          'name' => $term_name,
        ]);

        // If there is a matching term update the log category.
        if ($matching_term = reset($matching_terms)) {

          /** @var \Drupal\Core\Field\FieldItemListInterface $seed_dressing_filed */
          $seed_dressing_filed = $log->get('seed_dressing');
          $seed_dressing_filed->appendItem($matching_term);
          $log->save();

          // Log message.
          $log_id = $log->id();
          $log_message = "Updated log $log_id seed dressing: '$term_name'";
          \Drupal::logger('farm_rothamsted_quick')->info($log_message);
        }
      }
    }
  }
}

/**
 * Update input and drilling logs that incorrectly created material quantities.
 */
function farm_rothamsted_quick_post_update_material_quantities(&$sandbox = NULL) {

  // Define default quantity labels to filter by.
  $default_quantity_labels = [
    'Time taken',
    'Tractor hours (start)',
    'Tractor hours (end)',
    'Fuel use',
  ];

  // Define log type and quantity label filters for each quick form.
  $quick_form_quantity_labels = [
    'spraying' => [
      'log_type' => 'input',
      'quantity_labels' => [
        'Harvest interval',
        'Pressure',
        'Water volume',
        'Temperature (C)',
        'Wind speed',
        'Area sprayed',
        'Speed driven',
        'Tank volume remaining',
      ],
    ],
    'fertiliser' => [
      'log_type' => 'input',
      'quantity_labels' => [
        'Machine treated area',
        'Field treated area',
        'Total volume applied',
        'Target application rate',
      ],
    ],
    'drilling' => [
      'log_type' => 'drilling',
      'quantity_labels' => [
        'Seed rate',
        'Drilling rate',
        'Thousand grain weight (TGW)',
        'Seed Germination Test Result',
        'Target plant population',
        'Establishment average',
        'Drilling depth',
      ],
    ],
  ];

  // Update quantities for each quick form.
  foreach ($quick_form_quantity_labels as $quick_id => $quick_conditions) {

    // Get the log type to filter by.
    $log_type = $quick_conditions['log_type'];

    // Build quantity labels to filter by.
    $target_quantity_labels = array_merge($default_quantity_labels, $quick_conditions['quantity_labels']);

    // Build a subquery of quantity ids to convert.
    // Limit to logs from the quick form and specified log type.
    $log_quantity_subquery = \Drupal::database()->select('log__quick', 'lquick')
      ->distinct(TRUE)
      ->condition('lquick.bundle', $log_type)
      ->condition('lquick.quick_value', $quick_id);

    // Join log__quantity and quantity.
    // Limit to material quantities with the target quantity labels.
    $log_quantity_subquery->innerJoin('log__quantity', 'lquantity', 'lquantity.entity_id = lquick.entity_id');
    $log_quantity_subquery->innerJoin('quantity', 'q', 'q.id = lquantity.quantity_target_id');
    $log_quantity_subquery
      ->condition('q.type', 'material')
      ->condition('q.label', $target_quantity_labels, 'IN');

    // Select the quantity ids.
    $log_quantity_subquery->addField('q', 'id', 'quantity_id');

    // Update the type to standard for the target quantities.
    $quantities_affected = \Drupal::database()->update('quantity')
      ->fields([
        'type' => 'standard',
      ])
      ->condition('id', $log_quantity_subquery, 'IN')
      ->execute();

    // Add logger message.
    \Drupal::logger('farm_rothamsted_quick')->info("Converted $quantities_affected material quantities to standard quantities for quick form: $quick_id");
  }

}

/**
 * Remove missing asset references from logs created by quick forms.
 */
function farm_rothamsted_quick_post_update_2_11_3_fix_missing_asset_references(&$sandbox = NULL) {

  // Delete references to asset ID 0.
  \Drupal::database()->delete('log__asset')
    ->condition('asset_target_id', 0)
    ->execute();
  \Drupal::database()->delete('log_revision__asset')
    ->condition('asset_target_id', 0)
    ->execute();
}

/**
 * Install tagify module.
 */
function farm_rothamsted_quick_post_update_2_30_require_tagify(&$sandbox) {
  // Install the tagify module.
  if (!\Drupal::moduleHandler()->moduleExists('tagify')) {
    \Drupal::service('module_installer')->install(['tagify']);
  }
}

/**
 * Update log quantity labels from quick forms.
 */
function farm_rothamsted_quick_post_update_2_30_update_quantity_label(&$sandbox) {

  $quantity_label_map = [
    'Working width (m)' => 'Working width',
    'Depth worked (cm)' => 'Depth worked',
    'Thousand grain weight (TGW)' => 'Thousand grain weight',
    'Temperature (C)' => 'Temperature',
  ];

  $log_storage = \Drupal::entityTypeManager()->getStorage('log');

  // This function will be run as a batch operation. On the first run, we will
  // make preparations. This logic should only run once.
  if (!isset($sandbox['current_id'])) {

    // Query for logs with matching quantities.
    $query = $log_storage
      ->getAggregateQuery('OR')
      ->accessCheck(FALSE)
      ->sort('id');
    foreach (array_keys($quantity_label_map) as $old_label) {
      $query->condition('quantity.entity.label', $old_label);
    }

    // This returns an array of arrays like:
    // [
    //   "id" => "25598",
    //   "label" => "Tractor hours (start)",
    //   "log_field_data_id" => "7885",
    // ]
    $log_quantity_result = $query
      ->groupBy('quantity.entity.id')
      ->groupBy('quantity.entity.label')
      ->groupBy('id')
      ->execute();

    // Filter to only the quantity labels we care about.
    $log_quantity_result = array_filter($log_quantity_result, function ($log_quantity) use ($quantity_label_map) {
      return isset($log_quantity['label']) && isset($quantity_label_map[$log_quantity['label']]);
    });

    // Group filtered array by log ID. This way we only update each log one time.
    $grouped = [];
    foreach ($log_quantity_result as $item) {
      $grouped[$item['log_field_data_id']][] = $item;
    }

    // Save to sandbox as numerically indexed array starting at 0.
    $sandbox['log_quantity'] = array_values($grouped);

    // Track progress.
    $sandbox['current_id'] = 0;
    $sandbox['#finished'] = 0;
  }

  // Iterate through logs, 10 at a time.
  $quantity_count = count($sandbox['log_quantity']);
  $end_quantity = $sandbox['current_id'] + 10;
  $end_quantity = $end_quantity > $quantity_count ? $quantity_count : $end_quantity;
  for ($i = $sandbox['current_id']; $i < $end_quantity; $i++) {

    // Iterate the global counter.
    $sandbox['current_id']++;

    // Get set up quantity data for the log.
    if (empty($sandbox['log_quantity'][$sandbox['current_id']])) {
      continue;
    }
    $quantity_updates = $sandbox['log_quantity'][$sandbox['current_id']];

    // Load the log and quantity data.
    if (empty($quantity_updates[0]['log_field_data_id'])) {
      continue;
    }
    /** @var \Drupal\log\Entity\LogInterface $log */
    $log = $log_storage->load($quantity_updates[0]['log_field_data_id']);
    $log_quantity_data = $log->get('quantity')->getValue();

    // Update each quantity on the log.
    foreach ($quantity_updates as $quantity_update) {
      $quantity = Quantity::load($quantity_update['id']);

      // Update the quantity entity.
      $old_label = $quantity->get('label')->value;
      if (isset($quantity_label_map[$old_label])) {
        $quantity->set('label', $quantity_label_map[$old_label])->save();
        $quantity_revision = $quantity->getRevisionId();

        // Update revision in the log quantity data array.
        foreach ($log_quantity_data as $index => $log_quantity) {
          if ($log_quantity['target_id'] == $quantity->id()) {
            $log_quantity_data[$index]['target_revision_id'] = $quantity_revision;
          }
        }
      }

      // After quantities are updated, update the log.
      $log->set('quantity', $log_quantity_data);
      $log->setNewRevision();
      $log->setRevisionCreationTime(time());
      $log->setRevisionLogMessage('Update log quantity labels - see Github issue 859.');
      $log->setRevisionTranslationAffected(TRUE);
      $log->save();

      \Drupal::logger('farm_rothamsted_quick')->info("Updated log quantity labels: {$log->id()}");
    }
  }

  // Update progress.
  if (!empty($sandbox['log_quantity'])) {
    $sandbox['#finished'] = $sandbox['current_id'] / count($sandbox['log_quantity']);
  }
  else {
    $sandbox['#finished'] = 1;
  }

  return NULL;
}
