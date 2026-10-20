<?php

/**
 * @file
 * Post update functions for farm_rothamsted_researcher module.
 */

declare(strict_types=1);

/**
 * Force update the definition for the mismatched field.
 */
function farm_rothamsted_researcher_fix_mismatched_role_field() {
  $update_manager = \Drupal::entityDefinitionUpdateManager();
  if ($field_storage_definition = $update_manager->getFieldStorageDefinition('role', 'rothamsted_researcher')) {
    $update_manager->updateFieldStorageDefinition($field_storage_definition);
  }
}
