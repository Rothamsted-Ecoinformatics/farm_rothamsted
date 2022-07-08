<?php

/**
 * @file
 * Provides Views runtime hooks for farm_rothamsted_quick.module.
 */

use Drupal\views\Plugin\views\cache\CachePluginBase;
use Drupal\views\ViewExecutable;

/**
 * Implements hook_views_post_render().
 */
function farm_rothamsted_quick_views_post_render(ViewExecutable $view, array &$output, CachePluginBase $cache) {

  // Modify the title of the rothamsted_quick_logs view.
  // Workaround for https://www.drupal.org/project/drupal/issues/2663316
  if ($view->id() == 'rothamsted_quick_logs') {

    // In the future, we should add a views argument plugin for quick forms
    // and use this plugin to get the quick form title.
    $title = $view->getTitle();
    $output['#title'] = ucfirst(str_replace('_', ' ', $title));
  }

}
