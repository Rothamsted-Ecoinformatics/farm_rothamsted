<?php

/**
 * @file
 * Update hooks for farm_rothamsted.module.
 */

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\user\Entity\Role;
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

/**
 * Create seed dressing field.
 */
function farm_rothamsted_post_update_create_seed_dressing_field(&$sandbox = NULL) {

  // Seed dressing material reference.
  $field_info = [
    'type' => 'entity_reference',
    'label' => t('Seed dressing'),
    'target_type' => 'taxonomy_term',
    'target_bundle' => 'material_type',
    'auto_create' => FALSE,
    'multiple' => TRUE,
  ];
  $field_definition = \Drupal::service('farm_field.factory')->bundleFieldDefinition($field_info);
  \Drupal::entityDefinitionUpdateManager()->installFieldStorageDefinition(
    'seed_dressing',
    'log',
    'farm_rothamsted',
    $field_definition,
  );
}

/**
 * Delete rothamsted asset parent action.
 */
function farm_rothamsted_post_update_remove_rothamsted_asset_parent_action(&$sandbox = NULL) {
  $action_id = 'rothamsted_asset_parent_action';
  if (!\Drupal::configFactory()->get("system.action.$action_id")->isNew()) {
    \Drupal::configFactory()->getEditable("system.action.$action_id")->delete();
  }
}

/**
 * Create Rothamsted date format.
 */
function farm_rothamsted_post_update_2_10_1_create_rothamsted_date_format(&$sandbox = NULL) {
  DateFormat::create([
    'id' => 'farm_rothamsted_date',
    'label' => t('Rothamsted date format'),
    'pattern' => 'd/m/Y',
    'locked' => TRUE,
  ])->save();
}

/**
 * Enable datetime module.
 */
function farm_rothamsted_post_update_2_10_2_enable_experiment_research(&$sandbox = NULL) {
  if (!\Drupal::service('module_handler')->moduleExists('farm_rothamsted_experiment_research')) {
    \Drupal::service('module_installer')->install(['farm_rothamsted_experiment_research']);
  }
}

/**
 * Enable role submodule.
 */
function farm_rothamsted_post_update_2_17_enable_role_submodule(&$sandbox = NULL) {

  // Load current operator users.
  $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties([
    'roles' => ['farm_operator'],
  ]);

  // Delete old operator role.
  if ($old_role = Role::load('farm_operator')) {
    $old_role->delete();
  }

  // Enable roles submodule.
  if (!\Drupal::service('module_handler')->moduleExists('farm_rothamsted_roles')) {
    \Drupal::service('module_installer')->install(['farm_rothamsted_roles']);
  }

  // Grant old operators the rothamsted_operator_basic role.
  /** @var \Drupal\user\UserInterface $user */
  foreach ($users as $user) {
    $user->get('roles')->appendItem('rothamsted_operator_basic');
    $user->save();
  }
}
