<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_researcher\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Theme hook implementations for farm_rothamsted_researcher.
 */
class ThemeHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(): array {
    return [
      'rothamsted_researcher' => [
        'render element' => 'elements',
      ],
    ];
  }

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

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_links__toolbar_user')]
  public function preprocessLinksToolbarUser(array &$variables): void {
    $links = [
      'researcher_profile' => [
        'link' => [
          '#type' => 'link',
          '#title' => new TranslatableMarkup('View Researcher profile'),
          '#url' => Url::fromRoute('farm_rothamsted_researcher.current_user_researcher'),
        ],
        '#weight' => -10,
      ],
    ];
    $variables['links'] = $links + $variables['links'];
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_rothamsted_researcher__full')]
  public function preprocessRothamstedResearcherFull(array &$variables): void {
    // Helpful $content variable for templates.
    foreach (Element::children($variables['elements']) as $key) {
      $variables['content'][$key] = $variables['elements'][$key];
    }
    $variables['#attached']['library'][] = 'farm_ui_theme/layout';
    farm_ui_theme_build_stacked_twocol_layout($variables, 'rothamsted_researcher');
  }

}
