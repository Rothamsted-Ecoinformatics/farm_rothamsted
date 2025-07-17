<?php

/**
 * @file
 * Update hooks for farm_rothamsted.module.
 */

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\farm_rothamsted_researcher\Entity\RothamstedResearcher;
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

/**
 * Add fields to proposal entity.
 */
function farm_rothamsted_researcher_post_update_2_16_notification_field(&$sandbox = NULL) {

  // Create notification_enabled field.
  $field_definition = BaseFieldDefinition::create('boolean')
    ->setLabel('Notifications')
    ->setDefaultValue(TRUE)
    ->setRevisionable(TRUE);
  \Drupal::entityDefinitionUpdateManager()->installFieldStorageDefinition(
    'notification_enabled',
    'rothamsted_researcher',
    'farm_rothamsted_researcher',
    $field_definition,
  );

  // Set default value for existing researchers.
  \Drupal::database()->update('rothamsted_researcher_data')
    ->isNull('notification_enabled')
    ->fields(['notification_enabled' => TRUE])
    ->execute();
  \Drupal::database()->update('rothamsted_researcher_field_revision')
    ->isNull('notification_enabled')
    ->fields(['notification_enabled' => TRUE])
    ->execute();
}

/**
 * Remove researcher email notification fields.
 */
function farm_rothamsted_researcher_post_update_2_18_remove_email_notification_fields(&$sandbox = NULL) {

  // Remove the researcher notification field.
  $update_manager = \Drupal::entityDefinitionUpdateManager();
  $notification_field = $update_manager->getFieldStorageDefinition('notification_enabled', 'rothamsted_researcher');
  \Drupal::entityDefinitionUpdateManager()->uninstallFieldStorageDefinition($notification_field);

  // Remove the researcher email field.
  $update_manager = \Drupal::entityDefinitionUpdateManager();
  $email_field = $update_manager->getFieldStorageDefinition('email', 'rothamsted_researcher');
  \Drupal::entityDefinitionUpdateManager()->uninstallFieldStorageDefinition($email_field);
}

/**
 * Add comments to researchers.
 */
function farm_rothamsted_researcher_post_update_2_21_comments(&$sandbox = NULL) {

  // First enable farm_comment module.
  if (!\Drupal::service('module_handler')->moduleExists('farm_comment')) {
    \Drupal::service('module_installer')->install(['farm_comment']);
  }

  // Create comment type.
  $comment_type_id = 'rothamsted_researcher';
  $config_path = \Drupal::service('extension.list.module')->getPath('farm_rothamsted_researcher') . '/config/install';
  $configs = [
    "comment.type.$comment_type_id",
    "field.field.comment.$comment_type_id.comment_body",
  ];
  foreach ($configs as $config) {
    $data = Yaml::parseFile("$config_path/$config.yml");
    \Drupal::configFactory()->getEditable($config)->setData($data)->save(TRUE);
  }

  // Create new comment base field definition.
  /** @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $update_manager */
  $update_manager = \Drupal::entityDefinitionUpdateManager();
  $new_definition = farm_comment_base_field_definition($comment_type_id);
  $update_manager->installFieldStorageDefinition('comment', $comment_type_id, 'farm_rothamsted_experiment_research', $new_definition);

}

/**
 * Change cardinality of researcher.roles field to be unlimited.
 */
function farm_rothamsted_researcher_post_update_2_28_multiple_roles(&$sandbox = NULL) {

  # Query all current researcher roles.
  $database = \Drupal::database();
  $researcher_roles = $database->select('rothamsted_researcher_data', 'rrd')
    ->fields('rrd', ['id', 'role'])
    ->orderBy('id')
    ->execute()
    ->fetchAllKeyed();

  # Update field storage.
  $manager = \Drupal::entityDefinitionUpdateManager();
  $storage_definition = $manager->getFieldStorageDefinition('role', 'rothamsted_researcher');
  # Change cardinality to 1 because the role field in the RothamstedResearcher
  # class will have been updated to be unlimited when update hook is ran.
  # This ensures the correct rothamsted_researcher_data field is updated.
  $storage_definition->setCardinality(1);
  $manager->uninstallFieldStorageDefinition($storage_definition);

  # Change the cardinality back to unlimited and reinstall the field definition.
  # This ensures the correct rothamsted_researcher__role table will be created.
  $new_definition = $storage_definition->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
  $manager->installFieldStorageDefinition('role', 'rothamsted_researcher', 'farm_rothamsted_researcher', $new_definition);

  # Restore researcher roles.
  $researchers = RothamstedResearcher::loadMultiple();
  foreach ($researcher_roles as $id => $role) {
    if (isset($researchers[$id])) {
      $researchers[$id]->set('role', [$role]);
      $researchers[$id]->save();
      \Drupal::logger('farm_rothamsted_researcher')->notice("Restored role '$role' for researcher $id");
    }
  }
}
