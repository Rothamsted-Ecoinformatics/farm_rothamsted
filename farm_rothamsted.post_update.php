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
}
