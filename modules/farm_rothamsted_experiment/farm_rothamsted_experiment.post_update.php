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
  if (\Drupal::moduleHandler()->moduleExists('link')) {
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
