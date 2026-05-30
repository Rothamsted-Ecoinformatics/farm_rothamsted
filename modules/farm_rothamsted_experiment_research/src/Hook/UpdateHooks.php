<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_experiment_research\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Update hook implementations for farm_rothamsted_experiment_research.
 */
class UpdateHooks {

  /**
   * Implements hook_farm_update_managed_config().
   */
  #[Hook('farm_update_managed_config')]
  public function farmUpdateManagedConfig() {
    return [
      'user.role.rothamsted_research_editor',
      'user.role.rothamsted_research_lead',
      'user.role.rothamsted_research_restricted_viewer',
      'user.role.rothamsted_research_reviewer',
      'views.view.rothamsted_experiment',
      'views.view.rothamsted_experiment_design',
      'views.view.rothamsted_experiment_plan',
      'views.view.rothamsted_program',
      'views.view.rothamsted_proposal',
    ];
  }

}
