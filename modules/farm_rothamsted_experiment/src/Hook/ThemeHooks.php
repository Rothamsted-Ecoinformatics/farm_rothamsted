<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_experiment\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Theme hook implementations for farm_rothamsted_experiment.
 */
class ThemeHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_theme_registry_alter().
   */
  #[Hook('theme_registry_alter')]
  public function themeRegistryAlter(array &$theme_registry): void {

    // Do not use the two column layout on plans.
    if (isset($theme_registry['plan__full']['preprocess functions'])) {
      $theme_registry['plan__full']['preprocess functions'] = array_filter($theme_registry['plan__full']['preprocess functions'], fn ($f) => $f != 'farm_ui_theme_preprocess_plan__full');
    }
  }

  /**
   * Implements hook_farm_ui_theme_field_groups().
   */
  #[Hook('farm_ui_theme_field_groups')]
  public function farmUiThemeFieldGroups(string $entity_type, string $bundle): array {
    // Add a field group for group membership fields on logs.
    if ($entity_type === 'plan' && $bundle === 'rothamsted_experiment') {
      return [
        'locations' => [
          'location' => 'main',
          'title' => $this->t('Locations'),
          'weight' => 20,
        ],
        'deviations' => [
          'location' => 'main',
          'title' => $this->t('Deviations'),
          'weight' => 170,
        ],
      ];
    }
    return [];
  }

  /**
   * Implements hook_farm_ui_theme_field_group_items().
   */
  #[Hook('farm_ui_theme_field_group_items')]
  public function farmUiThemeFieldGroupItems(string $entity_type, string $bundle): array {
    if ($entity_type === 'plan' && $bundle === 'rothamsted_experiment') {
      return [
        'location' => 'locations',
        'asset' => 'locations',
        'columns_file' => 'file',
        'column_levels_file' => 'file',
        'plot_attributes_file' => 'file',
        'plot_geometry_file' => 'file',
        'experiment_plan_link' => 'file',
        'experiment_file_link' => 'file',
        'other_links' => 'file',
        'growing_conditions' => 'meta',
        'agreed_quote' => 'meta',
        'status_notes' => 'meta',
        'deviations' => 'deviations',
        'reason_for_failure' => 'deviations',
      ];
    }
    return [];
  }

}
