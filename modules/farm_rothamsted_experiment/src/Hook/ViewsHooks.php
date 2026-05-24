<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_experiment\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\plan\Entity\Plan;
use Drupal\views\ViewExecutable;

/**
 * Views hook implementations for farm_rothamsted_experiment.
 */
class ViewsHooks {

  use StringTranslationTrait;

  public function __construct(
    protected readonly RouteMatchInterface $routeMatch,
    protected readonly MessengerInterface $messenger,
  ) {
  }

  /**
   * Implements hook_views_data_alter().
   */
  #[Hook('views_data_alter')]
  public function viewsDataAlter(array &$data): void {

    // Use the column_levels filter for the plot column_descriptors_value.
    if (isset($data['asset__column_descriptors']['column_descriptors_value'])) {
      $data['asset__column_descriptors']['column_descriptors_value']['filter'] = [
        'id' => 'column_level',
      ];
    }

    // Use the plan_plot_types filter for the plot_type filter.
    if (isset($data['asset__plot_type']['plot_type_value'])) {
      $data['asset__plot_type']['plot_type_value']['filter'] = [
        'id' => 'plan_plot_type',
      ];
    }

    // Add a reverse relationship for all experiment plan logs.
    $data['log_field_data']['reverse__rothamsted_experiment_log'] = [
      'title' => $this->t('Study Plan'),
      'help' => $this->t('Relate each log with the rothamsted study plan ALL.'),
      'relationship' => [
        'id' => 'standard',
        'join_id' => 'rothamsted_experiment_logs',
        'base' => 'plan_field_data',
        'base field' => 'id',
        'relationship table' => 'rothamsted_experiment_plan_logs',
        'label' => $this->t('Plan'),
        'group' => $this->t('Log'),
        'entity_type' => 'plan',
      ],
    ];

    // Add a reverse relationship for only experiment plan logs via plan.asset.
    $data['log_field_data']['reverse__rothamsted_experiment_asset_log'] = [
      'title' => $this->t('Study Plan'),
      'help' => $this->t('Relate each log with the rothamsted study plan ASSET.'),
      'relationship' => [
        'id' => 'standard',
        'join_id' => 'rothamsted_experiment_asset_logs',
        'base' => 'plan_field_data',
        'base field' => 'id',
        'relationship table' => 'rothamsted_experiment_plan_asset_logs',
        'label' => $this->t('Plan'),
        'group' => $this->t('Log'),
        'entity_type' => 'plan',
      ],
    ];

    // Add a reverse relationship for only experiment plan logs via plan.plot.
    $data['log_field_data']['reverse__rothamsted_experiment_plot_log'] = [
      'title' => $this->t('Study Plan'),
      'help' => $this->t('Relate each log with the rothamsted study plan PLOT.'),
      'relationship' => [
        'id' => 'standard',
        'join_id' => 'rothamsted_experiment_plot_logs',
        'base' => 'plan_field_data',
        'base field' => 'id',
        'relationship table' => 'rothamsted_experiment_plan_plot_logs',
        'label' => $this->t('Plan'),
        'group' => $this->t('Log'),
        'entity_type' => 'plan',
      ],
    ];
  }

  /**
   * Implements hook_views_pre_view().
   */
  #[Hook('views_pre_view')]
  public function viewsPreView(ViewExecutable $view): void {

    // Bail if not the plan_plots view.
    if ($view->id() !== 'rothamsted_experiment_plan_plots' || !in_array($view->current_display, ['page', 'geojson'])) {
      return;
    }

    // Bail if there is no plan argument.
    if (empty($view->args)) {
      return;
    }

    $plan = Plan::load($view->args[0]);
    if (!empty($plan) && $plan->hasField('column_descriptors') && !$plan->get('column_descriptors')->isEmpty()) {

      // Load column_descriptors from json.
      $column_descriptors = json_decode($plan->get('column_descriptors')->value);

      /** @var \Drupal\views\Plugin\views\field\FieldHandlerInterface[] $fields */
      $fields = $view->getHandlers('field');
      foreach ($column_descriptors as $column) {

        // Build factor options from each factor type.
        $column_levels = [];
        $column_levels_filter_options = [];
        foreach ($column->column_levels as $index => $column_level) {
          $column_levels[$index] = $column_level->level_name;
          $column_levels_filter_options[$column_level->level_id] = "$column_level->level_name";
        }

        // Add a views field for each column.
        $column_id = $column->column_id;
        $field_id = "column_descriptor_$column_id";
        $fields[$field_id] = [
          'id' => $field_id,
          'table' => 'asset__column_descriptors',
          'field' => 'column_descriptors_value',
          'relationship' => 'none',
          'entity_type' => 'asset',
          'entity_field' => 'column_descriptors',
          'plugin_id' => 'field',
          'label' => $column->column_name,
          'type' => 'plot_column_descriptor',
          'settings' => [
            'column_levels' => $column_levels,
            'column_id' => $column_id,
            'raw' => FALSE,
            'value_only' => TRUE,
          ],
        ];

        // Add a views filter for each column.
        $filter = [
          'id' => $field_id,
          'table' => 'asset__column_descriptors',
          'field' => 'column_descriptors_value',
          'entity_type' => 'asset',
          'entity_field' => 'column_descriptors',
          'plugin_id' => 'column_level',
          'label' => $column->column_name,
          'settings' => [
            'column_id' => $column_id,
            'column_levels' => $column_levels,
            'column_options' => $column_levels_filter_options,
          ],
          'exposed' => TRUE,
          'expose' => [
            'operator_id' => $field_id . '_op',
            'label' => $column->column_name,
            'identifier' => $column_id,
            'multiple' => TRUE,
          ],
        ];
        $view->addHandler($view->current_display, 'filter', 'asset__column_descriptors', 'column_descriptors_value', $filter);
      }

      // Update the field handlers for the display.
      $view->getDisplay()->setOption('fields', $fields);
    }
  }

  /**
   * Implements hook_views_pre_render().
   */
  #[Hook('views_pre_render')]
  public function viewsPreRender(ViewExecutable $view): void {

    // Remove map from plot asset page view.
    if ($view->id() === 'farm_asset' && $view->current_display === 'page_type' && $view->args[0] === 'plot') {
      unset($view->attachment_before['asset_map']);
    }

    // Modify the experiment plots view.
    if ($view->id() === 'rothamsted_experiment_plan_plots' && $view->current_display === 'page') {

      // First add map to experiment plots view.
      $map = [
        '#type' => 'farm_map',
        '#map_type' => 'farm_rothamsted_experiment_plots',
      ];
      $map['#map_settings']['farm_rothamsted_experiment_plot_layer'] = [
        'plan' => $this->routeMatch->getRawParameter('plan'),
        'filters' => $view->getExposedInput(),
      ];

      // Add "All locations" layer.
      $map['#map_settings']['asset_type_layers']['all_locations'] = [
        'label' => $this->t('All locations'),
        'filters' => [
          'is_location' => 1,
        ],
        'color' => 'grey',
      ];

      // Add warning message if the view has multiple pages of results.
      $pager = $view->getPager();
      if ($pager && $pager->usePager()) {
        $total_items = $pager->getTotalItems();
        $items_per_page = $pager->getItemsPerPage();
        if ($total_items > $items_per_page) {
          $this->messenger->addWarning($this->t('Not all plots are displayed on this page. To view or add records to the full list of plots, use the Filter and set "Items per page" to "All"'));
        }
      }

      // Render the map.
      $view->attachment_before['asset_map'] = $map;
    }
  }

}
