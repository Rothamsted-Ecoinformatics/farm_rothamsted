<?php

namespace Drupal\farm_rothamsted_experiment_research;

use Drupal\user\RoleInterface;

/**
 * Adds research permissions to Rothamsted Roles.
 */
class RolePermissions {

  /**
   * Add permissions to default farmOS roles.
   *
   * @param \Drupal\user\RoleInterface $role
   *   The role to add permissions to.
   *
   * @return array
   *   An array of permission strings.
   */
  public function permissions(RoleInterface $role) {
    $perms = [];

    // Only add perms for specific roles.
    $role_perms_mapping = [
      'rothamsted_farm_manager' => ['create', 'view', 'update_any'],
      'rothamsted_farm_viewer' => ['view'],
      'rothamsted_data_admin' => ['create', 'view', 'update_any', 'revert_any', 'delete_any'],
      'rothamsted_operator_basic' => ['view'],
      'rothamsted_operator_advanced' => ['view'],
      'rothamsted_research_lead' => ['create', 'view', 'update_assigned'],
      'rothamsted_research_editor' => ['view', 'update_assigned'],
      'rothamsted_research_restricted_viewer' => ['view_assigned'],
      'rothamsted_research_reviewer' => ['view'],
    ];
    if (!isset($role_perms_mapping[$role->id()])) {
      return $perms;
    }

    // Define template permission strings for each operation.
    $operation_perm_mapping = [
      'create' => [
        'create {entity_type}',
      ],
      'view' => [
        'access {entity_type} overview',
        'view all {entity_type} revisions',
        'view any {entity_type}',
      ],
      'view_assigned' => [
        'access {entity_type} overview',
        'view all {entity_type} revisions',
        'view research_assigned {entity_type}',
      ],
      'update_any' => [
        'update any {entity_type}',
      ],
      'update_assigned' => [
        'update research_assigned {entity_type}',
        'update own {entity_type}',
      ],
      'revert_any' => [
        'revert all {entity_type} revisions',
      ],
      'delete_any' => [
        'delete any {entity_type}',
      ],
    ];

    $research_entities = [
      'rothamsted_proposal',
      'rothamsted_program',
      'rothamsted_experiment',
      'rothamsted_design',
    ];

    // Build operation permissions for each entity type.
    foreach ($role_perms_mapping[$role->id()] as $operation) {

      // Replace operation permission template strings with each entity type.
      $operation_permissions = $operation_perm_mapping[$operation];
      $operation_perms = array_merge(...array_values(array_map(function (string $entity_type) use ($operation_permissions) {
        return array_map(function (string $permission) use ($entity_type) {
          return str_replace('{entity_type}', $entity_type, $permission);
        }, $operation_permissions);
      }, $research_entities)));

      // Add to final perms.
      array_push($perms, ...$operation_perms);
    }

    return $perms;
  }

}
