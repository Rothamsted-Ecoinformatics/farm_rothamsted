<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_quick\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Update hook implementations for farm_rothamsted_quick.
 */
class UpdateHooks {

  /**
   * Implements hook_farm_update_managed_config().
   */
  #[Hook('farm_update_managed_config')]
  public function farmUpdateManagedConfig() {
    return [
      'views.view.rothamsted_quick_location_reference',
      'views.view.rothamsted_quick_logs',
    ];
  }

}
