<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_researcher\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Theme hook implementations for farm_rothamsted_researcher.
 */
class ThemeHooks {

  /**
   * Implements hook_theme_registry_alter().
   */
  #[Hook('theme_registry_alter')]
  public function themeRegistryAlter(array &$theme_registry): void {
    // Register preprocess function to add link to user toolbar.
    if (isset($theme_registry['links__toolbar_user']['preprocess functions'])) {
      $theme_registry['links__toolbar_user']['preprocess functions'][] = 'farm_rothamsted_researcher_preprocess_links__toolbar_user';
    }
  }

  /**
   * Implements hook_theme_suggestions_HOOK().
   */
  #[Hook('theme_suggestions_rothamsted_researcher')]
  public function themeSuggestionsRothamstedResearcher(array $variables): array {
    $suggestions = [];
    $sanitized_view_mode = strtr($variables['elements']['#view_mode'], '.', '_');
    $suggestions[] = 'rothamsted_researcher__' . $sanitized_view_mode;
    return $suggestions;
  }

}
