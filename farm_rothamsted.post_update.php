<?php

/**
 * @file
 * Update hooks for farm_rothamsted.module.
 */

use Drupal\views\Entity\View;
use Symfony\Component\Yaml\Yaml;

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

/**
 * Create drain structure type.
 */
function farm_rothamsted_post_update_create_drain_structure_type2(&$sandbox = NULL) {
  $structure_type = 'drain';
  $config_path = \Drupal::service('extension.list.module')->getPath('farm_rothamsted') . "/config/install/farm_structure.structure_type.$structure_type.yml";
  $data = Yaml::parseFile($config_path);
  \Drupal::configFactory()->getEditable("farm_structure.structure_type.$structure_type")->setData($data)->save(TRUE);
}

/**
 * Create rothamsted asset parent action.
 */
function farm_rothamsted_post_update_create_rothamsted_asset_parent_action(&$sandbox = NULL) {
  $action_id = 'rothamsted_asset_parent_action';
  $config_path = \Drupal::service('extension.list.module')->getPath('farm_rothamsted') . "/config/optional/system.action.$action_id.yml";
  $data = Yaml::parseFile($config_path);
  \Drupal::configFactory()->getEditable("system.action.$action_id")->setData($data)->save(TRUE);
}

/**
 * Create rothamsted_uncategorized_logs view.
 */
function farm_rothamsted_post_update_create_rothamsted_uncategorized_logs_view(&$sandbox = NULL) {

  // Only create the view if views is enabled.
  $view_id = 'rothamsted_uncategorized_logs';
  if (\Drupal::moduleHandler()->moduleExists('views') && !View::load($view_id)) {
    $config_path = \Drupal::service('extension.list.module')->getPath('farm_rothamsted') . "/config/optional/views.view.$view_id.yml";
    $data = Yaml::parseFile($config_path);
    \Drupal::configFactory()->getEditable("views.view.$view_id")->setData($data)->save(TRUE);
  }
}
