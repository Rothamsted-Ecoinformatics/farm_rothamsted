<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_dashboard\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Dashboard hook implementations for farm_rothamsted_dashboard.
 */
class DashboardHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_farm_dashboard_groups().
   */
  #[Hook('farm_dashboard_groups')]
  public function farmDashboardGroups(): array {
    return [
      'top' => [
        'rothamsted_user_studies' => [
          '#type' => 'details',
          '#open' => FALSE,
          '#title' => $this->t('My Studies'),
          '#weight' => 105,
          '#attributes' => [
            'class' => ['dashboard-pane'],
          ],
        ],
        'rothamsted_user_proposals' => [
          '#type' => 'details',
          '#open' => FALSE,
          '#title' => $this->t('My Proposals'),
          '#weight' => 110,
          '#attributes' => [
            'class' => ['dashboard-pane'],
          ],
        ],
      ],
    ];
  }

  /**
   * Implements hook_farm_dashboard_panes().
   */
  #[Hook('farm_dashboard_panes')]
  public function farmDashboardPanes(): array {
    return [
      'rothamsted_search' => [
        'block' => 'rothamsted_search',
        'title' => $this->t('Search'),
        'region' => 'top',
        'weight' => 100,
      ],
      'rothamsted_user_proposals' => [
        'block' => 'rothamsted_user_proposals',
        'region' => 'top',
        'group' => 'rothamsted_user_proposals',
      ],
      'rothamsted_user_studies' => [
        'block' => 'rothamsted_user_studies',
        'region' => 'top',
        'group' => 'rothamsted_user_studies',
      ],
    ];
  }

  /**
   * Implements hook_farm_dashboard_panes_alter().
   */
  #[Hook('farm_dashboard_panes_alter')]
  public function farmDashboardPanesAlter(array &$panes): void {
    $allowed_panes = [
      'rothamsted_search',
      'rothamsted_user_proposals',
      'rothamsted_user_studies',
      'dashboard_map',
    ];
    foreach (array_keys($panes) as $pane) {
      if (!in_array($pane, $allowed_panes)) {
        unset($panes[$pane]);
      }
    }
  }

}
