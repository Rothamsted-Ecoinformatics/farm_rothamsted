<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_experiment_research\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Local task hook implementations for farm_rothamsted_experiment_research.
 */
class LocalTaskHooks {

  /**
   * Implements hook_local_tasks_alter().
   */
  #[Hook('local_tasks_alter')]
  public function localTasksAlter(array &$local_tasks): void {
    // Disable Drupal core revisions local tasks.
    $target_entity_types = [
      'rothamsted_program',
      'rothamsted_proposal',
      'rothamsted_experiment',
      'rothamsted_design',
    ];
    foreach ($target_entity_types as $entity_type) {
      unset($local_tasks['entity.version_history:' . $entity_type . '.version_history']);
    }
  }

}
