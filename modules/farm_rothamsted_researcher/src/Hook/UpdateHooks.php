<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_researcher\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Update hook implementations for farm_rothamsted_researcher.
 */
class UpdateHooks {

  /**
   * Implements hook_farm_update_managed_config().
   */
  #[Hook('farm_update_managed_config')]
  public function farmUpdateManagedConfig() {
    return [
      'views.view.rothamsted_researcher',
      'views.view.rothamsted_researcher_reference',
    ];
  }

}
