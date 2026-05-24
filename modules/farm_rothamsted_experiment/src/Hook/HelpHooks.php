<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_experiment\Hook;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\asset\Entity\AssetInterface;
use Drupal\plan\Entity\Plan;
use Drupal\plan\Entity\PlanInterface;

/**
 * Help hook implementations for farm_rothamsted_experiment.
 */
class HelpHooks {

  /**
   * Constructs a HelpHooks object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help(string $route_name, RouteMatchInterface $route_match): array {
    $output = [];

    // All routes that are plan log pages.
    if (!str_starts_with($route_name, 'view.rothamsted_experiment_plan_logs.')) {
      return $output;
    }

    // Default values for plan log help text.
    $title = new TranslatableMarkup('Help');
    $description = NULL;
    $asset_list = TRUE;
    $plot_link = TRUE;

    // Customize help text for each log view.
    switch ($route_name) {
      case 'view.rothamsted_experiment_plan_logs.page':
        $title = new TranslatableMarkup('All logs associated with this experiment');
        $description = new TranslatableMarkup('This page includes all logs referencing plots or other assets associated with this experiment.');
        break;

      case 'view.rothamsted_experiment_plan_logs.page_plot':
        $title = new TranslatableMarkup('Logs referencing plots');
        $description = new TranslatableMarkup("This page includes logs that reference the experiment plots.");
        $asset_list = FALSE;
        break;

      case 'view.rothamsted_experiment_plan_logs.page_asset':
        $title = new TranslatableMarkup('Logs referencing other experiment assets');
        $description = new TranslatableMarkup('This page includes logs that reference other assets associated with this experiment.');
        $plot_link = FALSE;
        break;
    }

    // Start details element with list of things to link to.
    $details = [
      '#type' => 'details',
      '#title' => $title,
      '#description' => $description,
    ];
    $details['list'] = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => [],
    ];

    // Get the plan.
    $plan = $route_match->getParameter('plan');
    if (!$plan instanceof PlanInterface) {
      $plan = Plan::load($plan);
    }

    // Add link to plots page.
    if ($plot_link) {
      $plot_url = Url::fromRoute('view.rothamsted_experiment_plan_plots.page', ['plan' => $plan->id()]);
      $link = Link::fromTextAndUrl(new TranslatableMarkup('Plots'), $plot_url);
      $details['list']['#items'][] = $link->toRenderable();
    }

    // Add asset list.
    if ($asset_list) {
      $assets = $plan->get('asset')->referencedEntities();
      $labels = array_map(function (AssetInterface $asset) {
        return $asset->toLink()->toRenderable();
      }, $assets);
      array_push($details['list']['#items'], ...$labels);
    }

    $output['details'] = $details;

    return $output;
  }

}
