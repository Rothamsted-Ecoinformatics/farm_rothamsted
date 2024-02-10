<?php

/**
 * @file
 * Post update functions for farm_rothamsted_roles module.
 */

use Drupal\consumers\Entity\Consumer;

/**
 * Create rothamsted consumer.
 */
function farm_rothamsted_roles_post_update_2_19_create_consumer(&$sandbox = NULL) {

  // Enable farm_api and password grant module.
  if (!\Drupal::service('module_handler')->moduleExists('farm_api')) {
    \Drupal::service('module_installer')->install(['farm_api']);
  }
  if (!\Drupal::service('module_handler')->moduleExists('simple_oauth_password_grant')) {
    \Drupal::service('module_installer')->install([' simple_oauth_password_grant']);
  }

  // Check for an existing rothamsted consumer.
  $consumers = \Drupal::entityTypeManager()->getStorage('consumer')
    ->loadByProperties(['client_id' => 'rothamsted']);

  // If not found, create the rothamsted consumer.
  if (empty($consumers)) {
    $consumer = Consumer::create([
      'label' => 'Rothamsted',
      'client_id' => 'rothamsted',
      'access_token_expiration' => 3600,
      'grant_types' => [
        'refresh_token',
        'password',
      ],
      'is_default' => FALSE,
      'secret' => NULL,
      'confidential' => FALSE,
      'third_party' => FALSE,
    ]);
    $consumer->save();
  }
}
