<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_researcher\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Local task hook implementations for farm_rothamsted_researcher.
 */
class LocalTaskHooks {

  /**
   * Implements hook_local_tasks_alter().
   */
  #[Hook('local_tasks_alter')]
  public function localTasksAlter(array &$local_tasks): void {
    // Disable Drupal core revisions local tasks.
    unset($local_tasks['entity.version_history:rothamsted_researcher.version_history']);
  }

}
