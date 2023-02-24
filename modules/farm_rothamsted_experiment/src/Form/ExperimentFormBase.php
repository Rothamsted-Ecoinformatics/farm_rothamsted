<?php

namespace Drupal\farm_rothamsted_experiment\Form;

use Drupal\asset\Entity\AssetInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\farm_rothamsted\Traits\QuickFileTrait;
use Drupal\plan\Entity\PlanInterface;

/**
 * Base form with helper methods for experiment plans.
 */
abstract class ExperimentFormBase extends FormBase {

  use QuickFileTrait;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, PlanInterface $plan = NULL) {
    return $form;
  }

  /**
   * Helper function to get the experiment boundary.
   *
   * @param \Drupal\plan\Entity\PlanInterface $plan
   *   The experiment plan.
   *
   * @return \Drupal\asset\Entity\AssetInterface|null
   *   The experiment boundary asset or null.
   */
  public function experimentBoundary(PlanInterface $plan): ?AssetInterface {
    $boundary = NULL;
    /** @var \Drupal\asset\Entity\AssetInterface[] $plan_assets */
    $plan_assets = $plan->get('asset')->referencedEntities();
    foreach ($plan_assets as $plan_asset) {
      if ($plan_asset->bundle() == 'land' && $plan_asset->get('land_type')->value == 'experiment_boundary') {
        $boundary = $plan_asset;
        break;
      }
    }
    return $boundary;
  }

}
