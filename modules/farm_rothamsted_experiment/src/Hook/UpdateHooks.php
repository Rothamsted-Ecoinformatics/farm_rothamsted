<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_experiment\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Update hook implementations for farm_rothamsted_experiment.
 */
class UpdateHooks {

  /**
   * Implements hook_farm_update_managed_config().
   */
  #[Hook('farm_update_managed_config')]
  public function farmUpdateManagedConfig() {
    return [
      'views.view.rothamsted_experiment_plan_logs',
      'views.view.rothamsted_experiment_plan_plots',
      'views.view.rothamsted_experiment_plans',
    ];
  }

}
