<?php

/**
 * @file
 * Update hooks for farm_rothamsted.module.
 */

/**
 * Add storage_location field to harvest logs.
 */
function farm_rothamsted_post_update_add_harvest_storage_location_field(&$sandbox = NULL) {

  // Add storage_location field.
  $field_info = [
    'type' => 'entity_reference',
    'label' => t('Storage location'),
    'description' => t('The harvest storage location.'),
    'target_type' => 'asset',
    'target_bundle' => 'structure',
    'multiple' => TRUE,
    'weight' => [
      'form' => 90,
      'view' => 90,
    ],
  ];
  $field_definition = \Drupal::service('farm_field.factory')->bundleFieldDefinition($field_info);
  \Drupal::entityDefinitionUpdateManager()->installFieldStorageDefinition('storage_location', 'log', 'farm_rothamsted', $field_definition);

  // Update trailer harvest logs to use the storage_location field.
  $trailer_harvest_logs = \Drupal::entityTypeManager()->getStorage('log')->loadByProperties([
    'quick' => 'trailer_harvest',
    'location.entity:asset.type' => 'structure',
  ]);
  foreach ($trailer_harvest_logs as $log) {

    // Get location field objects.
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $log_location */
    $log_location = $log->get('location');
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $log_storage_location */
    $log_storage_location = $log->get('storage_location');

    // Move structure assets in the locations field to storage_locations field.
    $locations = [];
    $storage_locations = [];
    foreach ($log_location->referencedEntities() as $asset) {
      if ($asset->bundle() === 'structure') {
        $storage_locations[] = $asset;
      }
      else {
        $locations[] = $asset;
      }
    }

    // Update both fields and save log.
    $log_location->setValue($locations);
    $log_storage_location->setValue($storage_locations);
    $log->save();
  }

}
