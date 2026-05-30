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
      'farm_flag.flag.plot_discard',
      'farm_flag.flag.plot_missing',
      'farm_flag.flag.plot_partial_discard',
      'farm_flag.flag.plot_restriction_drilling_date',
      'farm_flag.flag.plot_restriction_fungicide',
      'farm_flag.flag.plot_restriction_gm_material',
      'farm_flag.flag.plot_restriction_herbicide',
      'farm_flag.flag.plot_restriction_insecticide',
      'farm_flag.flag.plot_restriction_irrigation',
      'farm_flag.flag.plot_restriction_liming',
      'farm_flag.flag.plot_restriction_magnesium',
      'farm_flag.flag.plot_restriction_micronutrient',
      'farm_flag.flag.plot_restriction_molluscicides',
      'farm_flag.flag.plot_restriction_nematicide',
      'farm_flag.flag.plot_restriction_nitrogen',
      'farm_flag.flag.plot_restriction_phosphorous',
      'farm_flag.flag.plot_restriction_physical',
      'farm_flag.flag.plot_restriction_plant_growth_regulator',
      'farm_flag.flag.plot_restriction_post_harvest_sampling',
      'farm_flag.flag.plot_restriction_potassium',
      'farm_flag.flag.plot_restriction_pre_harvest_sampling',
      'farm_flag.flag.plot_restriction_seed_rate',
      'farm_flag.flag.plot_restriction_seed_treatments',
      'farm_flag.flag.plot_restriction_sulphur',
      'farm_flag.flag.plot_restriction_variety',
      'farm_flag.flag.plot_substituted',
      'farm_flag.flag.plot_swapped',
      'farm_flag.flag.rothamsted_experiment_failed',
      'views.view.rothamsted_experiment_plan_logs',
      'views.view.rothamsted_experiment_plan_plots',
      'views.view.rothamsted_experiment_plans',
    ];
  }

}
