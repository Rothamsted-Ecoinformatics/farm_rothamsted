<?php
/**
 * @file
 * Farm access hooks implemented by Farm Rothamsted module.
 */

/**
 * Implements hook_farm_access_roles().
 */
function farm_rothamsted_farm_access_roles() {

  // Create a Farm Operator role with no extra permissions.
  // This is used to filter the "Operator" user options in quick forms.
  $roles = array(
    'farm_operator' => array(
      'name' => 'Farm Operator',
    ),
  );
  return $roles;
}
