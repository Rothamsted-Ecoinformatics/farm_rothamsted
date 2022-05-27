<?php

namespace Drupal\farm_rothamsted_experiment\Plugin\views\filter;

use Drupal\Core\Database\Connection;
use Drupal\views\Plugin\views\filter\ManyToOne;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Custom views filter for the plot_type value.
 *
 * Only displays plot_type values for the plots associated with the plan.
 *
 * @ViewsFilter("plan_plot_type")
 */
class PlanPlotType extends ManyToOne {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new Date object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {

    // Same logic as parent method.
    if (isset($this->valueOptions)) {
      return $this->valueOptions;
    }

    // Load plot type options.
    $plot_type_options = farm_rothamsted_experiment_plot_type_options();

    // Check if a plan id is provided.
    // @todo Should the plan argument or id be configuration for the filter?
    if (isset($this->view->args[0])) {

      // Query for all plot_type values associated with the plan's plots.
      $plan_id = $this->view->args[0];
      $query = $this->database->select('asset__plot_type', 'apt')
        ->fields('apt', ['plot_type_value'])
        ->distinct(TRUE);
      $query->innerJoin('plan__plot', 'pp', 'pp.entity_id = :plan_id and pp.deleted = 0 and pp.plot_target_id = apt.entity_id', [':plan_id' => $plan_id]);
      $results = $query->execute()->fetchCol();

      // Limit the plot type options to the results.
      if (!empty($results)) {
        $plot_type_options = array_intersect_key($plot_type_options, array_flip($results));
      }
    }

    // Set the value options.
    $this->valueOptions = $plot_type_options;

    return $this->valueOptions;
  }

}
