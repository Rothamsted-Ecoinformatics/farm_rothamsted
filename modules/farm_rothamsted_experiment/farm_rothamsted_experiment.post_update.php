<?php

/**
 * @file
 * Post update functions for farm_rothamsted_experiment module.
 */

declare(strict_types=1);

use Drupal\system\Entity\Action;

/**
 * Delete plan_status_archived action config.
 */
function farm_rothamsted_experiment_post_update_remove_plan_status_archived(&$sandbox) {
  if ($action = Action::load('plan_status_archived')) {
    $action->delete();
  }
}
