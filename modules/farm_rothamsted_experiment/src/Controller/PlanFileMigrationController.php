<?php

namespace Drupal\farm_rothamsted_experiment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Controller for listing plans that need file migration.
 */
class PlanFileMigrationController extends ControllerBase {

  /**
   * List plans that need file migration.
   *
   * Shows plans with column_descriptors populated but missing the 4 new file fields.
   *
   * @return array
   *   Render array.
   */
  public function listPlans() {
    $plan_storage = $this->entityTypeManager()->getStorage('plan');

    // Query plans with column_descriptors populated.
    $query = $plan_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'rothamsted_experiment')
      ->exists('column_descriptors')
      ->sort('id', 'ASC');

    // Filter to plans that don't have the 4 new file fields populated.
    $query->notExists('columns_file');
    $query->notExists('column_levels_file');
    $query->notExists('plot_attributes_file');
    $query->notExists('plot_geometry_file');

    $plan_ids = $query->execute();

    if (empty($plan_ids)) {
      return [
        '#markup' => $this->t('No plans require file migration.'),
      ];
    }

    $plans = $plan_storage->loadMultiple($plan_ids);

    $rows = [];
    foreach ($plans as $plan) {
      $migrate_url = Url::fromRoute('farm_rothamsted_experiment.plan_file_migration_form', ['plan' => $plan->id()]);

      // Get the last updated timestamp.
      $last_updated = \Drupal::service('date.formatter')->format($plan->getChangedTime(), 'short');

      $rows[] = [
        $plan->id(),
        $plan->label(),
        $last_updated,
        Link::fromTextAndUrl($this->t('Migrate files'), $migrate_url),
      ];
    }

    $build = [
      '#type' => 'table',
      '#header' => [
        $this->t('Plan ID'),
        $this->t('Plan Name'),
        $this->t('Last Updated'),
        $this->t('Actions'),
      ],
      '#rows' => $rows,
      '#empty' => $this->t('No plans require file migration.'),
    ];

    return $build;
  }

}
