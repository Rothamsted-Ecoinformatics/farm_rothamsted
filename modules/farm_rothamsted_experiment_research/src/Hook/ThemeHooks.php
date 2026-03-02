<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_experiment_research\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;

/**
 * Theme hook implementations for farm_rothamsted_experiment_research.
 */
class ThemeHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(): array {
    return [
      'rothamsted_proposal' => [
        'render element' => 'elements',
        'template' => 'rothamsted-research-entity',
        'preprocess functions' => [
          'farm_rothamsted_experiment_research_preprocess_rothamsted_research_entity',
        ],
      ],
      'rothamsted_program' => [
        'render element' => 'elements',
        'template' => 'rothamsted-research-entity',
        'preprocess functions' => [
          'farm_rothamsted_experiment_research_preprocess_rothamsted_research_entity',
        ],
      ],
      'rothamsted_experiment' => [
        'render element' => 'elements',
        'template' => 'rothamsted-research-entity',
        'preprocess functions' => [
          'farm_rothamsted_experiment_research_preprocess_rothamsted_research_entity',
        ],
      ],
      'rothamsted_design' => [
        'render element' => 'elements',
        'template' => 'rothamsted-research-entity',
        'preprocess functions' => [
          'farm_rothamsted_experiment_research_preprocess_rothamsted_research_entity',
        ],
      ],
    ];
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_rothamsted_research_entity')]
  public function preprocessRothamstedResearchEntity(array &$variables): void {

    // Make sure this is a research entity.
    if (isset($variables['theme_hook_original']) && isset($variables['elements']["#{$variables['theme_hook_original']}"])) {
      $entity_type_id = $variables['theme_hook_original'];
    }
    if (empty($entity_type_id)) {
      return;
    }

    // Helpful $content variable for templates.
    foreach (Element::children($variables['elements']) as $key) {
      $variables['content'][$key] = $variables['elements'][$key];
    }
    $variables['#attached']['library'][] = 'farm_ui_theme/layout';
    farm_ui_theme_build_stacked_twocol_layout($variables, $entity_type_id);
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_toolbar')]
  public function preprocessToolbar(array &$variables): void {
    $variables['#attached']['library'][] = 'farm_rothamsted_experiment_research/toolbar';
  }

}
