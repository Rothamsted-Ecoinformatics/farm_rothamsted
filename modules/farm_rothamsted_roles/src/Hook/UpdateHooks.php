<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_roles\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Update hook implementations for farm_rothamsted_roles.
 */
class UpdateHooks {

  /**
   * Implements hook_farm_update_managed_config().
   */
  #[Hook('farm_update_managed_config')]
  public function farmUpdateManagedConfig() {
    return [
      'user.role.rothamsted_data_admin',
      'user.role.rothamsted_farm_manager',
      'user.role.rothamsted_farm_viewer',
      'user.role.rothamsted_operator_advanced',
      'user.role.rothamsted_operator_basic',
    ];
  }

}
