<?php

/**
 * @file
 * Update hooks for farm_rothamsted_notification.module.
 */

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\Entity\User;

/**
 * Move email notifications from researcher to user entity.
 */
function farm_rothamsted_notification_post_update_2_18_move_email_notification_field(&$sandbox = NULL) {

  // Create notification_enabled field.
  $field_definition = BaseFieldDefinition::create('boolean')
    ->setLabel('Email notifications')
    ->setDefaultValue(TRUE)
    ->setInitialValue(TRUE)
    ->setRevisionable(TRUE);
  \Drupal::entityDefinitionUpdateManager()->installFieldStorageDefinition(
    'rothamsted_notification_email',
    'user',
    'farm_rothamsted_notification',
    $field_definition,
  );

  // Fetch researcher users that previously had notifications disabled.
  // Update their user values since the new user email field defaults to True.
  $user_ids = \Drupal::database()->select('rothamsted_researcher_data', 'rrd')
    ->fields('rrd', ['farm_user'])
    ->isNotNull('rrd.farm_user')
    ->condition('rrd.notification_enabled', FALSE)
    ->execute()
    ->fetchCol();
  if (is_array($user_ids) && count($user_ids) > 0) {
    $users = User::loadMultiple($user_ids);
    foreach ($users as $user) {
      $user->set('rothamsted_notification_email', FALSE);
      $user->save();
    }
  }

  // Remove the researcher notification field.
  $update_manager = \Drupal::entityDefinitionUpdateManager();
  $notification_field = $update_manager->getFieldStorageDefinition('notification_enabled', 'rothamsted_researcher');
  \Drupal::entityDefinitionUpdateManager()->uninstallFieldStorageDefinition($notification_field);
}
