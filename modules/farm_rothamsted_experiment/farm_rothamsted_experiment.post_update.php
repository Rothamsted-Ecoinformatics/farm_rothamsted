<?php

/**
 * @file
 * Update hooks for farm_rothamsted_experiment.module.
 */

use Drupal\entity\BundleFieldDefinition;
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

/**
 * Create experiment_boundary land type.
 */
function farm_rothamsted_experiment_post_update_create_experiment_link_fields(&$sandbox = NULL) {

  // Install the link module.
  if (!\Drupal::moduleHandler()->moduleExists('link')) {
    \Drupal::service('module_installer')->install(['link']);
  }

  // Experiment file link fields.
  $fields['experiment_plan_link'] = BundleFieldDefinition::create('link')
    ->setLabel(t('Experiment plan'))
    ->setRequired(FALSE)
    ->setRevisionable(TRUE)
    ->setSettings([
      'title' => DRUPAL_DISABLED,
      // @see LinkItemInterface::LINK_EXTERNAL.
      'link_type' => 0x10,
    ])
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);
  $fields['experiment_file_link'] = BundleFieldDefinition::create('link')
    ->setLabel(t('Experiment file'))
    ->setRequired(FALSE)
    ->setRevisionable(TRUE)
    ->setSettings([
      'title' => DRUPAL_DISABLED,
      // @see LinkItemInterface::LINK_EXTERNAL.
      'link_type' => 0x10,
    ])
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);

  // Install each field definition.
  foreach ($fields as $field_name => $field_definition) {
    \Drupal::entityDefinitionUpdateManager()->installFieldStorageDefinition(
      $field_name,
      'plan',
      'farm_rothamsted_experiment',
      $field_definition,
    );
  }

}

/**
 * Uninstall experiment people fields.
 */
function farm_rothamsted_experiment_post_update_uninstall_people_fields(&$sandbox = NULL) {

  // Fields to uninstall.
  $uninstall_definitions = [];

  // Build field definitions for old people and email fields.
  $uninstall_field_info = [
    'principle_investigator' => [
      'type' => 'string',
      'label' => t('Principle Investigator'),
      'description' => t('The lead scientist(s) associated with the experiment.'),
      'multiple' => TRUE,
    ],
    'data_steward' => [
      'type' => 'string',
      'label' => t('Data Steward'),
      'description' => t('The data steward(s) associated with the experiment.'),
      'multiple' => TRUE,
    ],
    'statistician' => [
      'type' => 'string',
      'label' => t('Statistician'),
      'description' => t('The statistician(s) associated with the experiment.'),
    ],
    'primary_contact' => [
      'type' => 'string',
      'label' => t('Primary Contact'),
      'description' => t('The primary contact for this experiment'),
    ],
    'secondary_contact' => [
      'type' => 'string',
      'label' => t('Secondary Contact'),
      'description' => t('The secondary contact for this experiment.'),
    ],
  ];
  foreach ($uninstall_field_info as $field_name => $field_info) {
    $uninstall_definitions[$field_name] = \Drupal::service('farm_field.factory')->bundleFieldDefinition($field_info);
  }

  // Build field definitions for contact email fields.
  $uninstall_definitions['primary_contact_email'] = BundleFieldDefinition::create('email')
    ->setLabel(t('Primary Contact Email'))
    ->setDescription(t('The e-mail address of the primary contact.'))
    ->setRevisionable(TRUE)
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);
  $uninstall_definitions['secondary_contact_email'] = BundleFieldDefinition::create('email')
    ->setLabel(t('Secondary Contact Email'))
    ->setDescription(t('The e-mail address of the secondary contact.'))
    ->setRevisionable(TRUE)
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);

  // Uninstall each definition.
  foreach ($uninstall_definitions as $field_name => $field_definition) {
    // Set the field name, entity type and bundle to complete the field
    // storage definition.
    $field_definition
      ->setName($field_name)
      ->setTargetEntityTypeId('plan')
      ->setTargetBundle('rothamsted_experiment');
    \Drupal::entityDefinitionUpdateManager()->uninstallFieldStorageDefinition($field_definition);
  }
}

/**
 * Create experiment contact field.
 */
function farm_rothamsted_experiment_post_update_create_contact_field(&$sandbox = NULL) {

  // User reference.
  $field_info = [
    'type' => 'entity_reference',
    'label' => t('Contacts'),
    'target_type' => 'user',
    'multiple' => TRUE,
  ];
  $field_definition = \Drupal::service('farm_field.factory')->bundleFieldDefinition($field_info);
  \Drupal::entityDefinitionUpdateManager()->installFieldStorageDefinition(
    'contact',
    'plan',
    'farm_rothamsted_experiment',
    $field_definition,
  );
}

/**
 * Change the location field to reference location assets.
 */
function farm_rothamsted_experiment_post_update_location_field_reference_asset(&$sandbox = NULL) {

  // Build and uninstall the old field definition.
  $field_definition = \Drupal::service('farm_field.factory')->bundleFieldDefinition([
    'type' => 'string',
    'label' => t('Field Location(s)'),
    'multiple' => TRUE,
  ]);
  $field_definition
    ->setName('location')
    ->setTargetEntityTypeId('plan')
    ->setTargetBundle('rothamsted_experiment');
  \Drupal::entityDefinitionUpdateManager()->uninstallFieldStorageDefinition($field_definition);

  // Build and install the new field definition.
  $field_definition = \Drupal::service('farm_field.factory')->bundleFieldDefinition([
    'type' => 'entity_reference',
    'label' => t('Field Location(s)'),
    'target_type' => 'asset',
    'target_bundle' => 'land',
    'multiple' => TRUE,
  ]);
  \Drupal::entityDefinitionUpdateManager()->installFieldStorageDefinition(
    'location',
    'plan',
    'farm_rothamsted_experiment',
    $field_definition,
  );
}

/**
 * Update plot assets to not be locations.
 */
function farm_rothamsted_experiment_post_update_update_plot_location(&$sandbox = NULL) {

  // Update asset_field_data.
  \Drupal::database()->update('asset_field_data')
    ->fields([
      'is_location' => 0,
    ])
    ->condition('type', 'plot')
    ->execute();

  // Subselect plot asset IDs for updating asset_field_revision.
  $plot_ids = \Drupal::database()->select('asset_field_data', 'afd')
    ->fields('afd', ['id'])
    ->condition('type', 'plot');

  // Update asset_field_revision.
  \Drupal::database()->update('asset_field_revision')
    ->fields([
      'is_location' => 0,
    ])
    ->condition('id', $plot_ids, 'IN')
    ->execute();

  // Invalidate cache for plot assets.
  /** @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator */
  $cache_tags_invalidator = Drupal::service('cache_tags.invalidator');
  $cache_tags_invalidator->invalidateTags(['asset_list', 'asset_list:plot']);
}

/**
 * Create rothamsted_sponsor and rothamsted_experiment_admin roles.
 */
function farm_rothamsted_experiment_post_update_create_sponsor_experiment_admin_roles(&$sandbox = NULL) {
  foreach (['rothamsted_experiment_admin', 'rothamsted_sponsor'] as $role_id) {
    $config_path = \Drupal::service('extension.list.module')->getPath('farm_rothamsted_experiment') . "/config/install/user.role.$role_id.yml";
    $data = Yaml::parseFile($config_path);
    \Drupal::configFactory()->getEditable("user.role.$role_id")->setData($data)->save(TRUE);
  }
}
