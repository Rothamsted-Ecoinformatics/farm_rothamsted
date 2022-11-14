<?php

namespace Drupal\farm_rothamsted_experiment\Plugin\views\join;

use Drupal\views\Plugin\views\join\JoinPluginBase;

/**
 * Join handler to relate logs to rothamsted experiment plans using plots.
 *
 * Joins logs to assets and assets to plans via only the plan.plot field.
 *
 * @ViewsJoin("rothamsted_experiment_plot_logs")
 */
class RothamstedExperimentPlotLogs extends JoinPluginBase {

  /**
   * Builds the SQL for the join this object represents.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $select_query
   *   The select query object.
   * @param string $table
   *   The base table to join.
   * @param \Drupal\views\Plugin\views\query\QueryPluginBase $view_query
   *   The source views query.
   */
  // @codingStandardsIgnoreStart
  public function buildJoin($select_query, $table, $view_query) {
    // @codingStandardsIgnoreEnd

    // Build subquery to select all logs that reference assets assigned to the
    // plan.plot field.
    $sub_query = \Drupal::database()->select('log__asset', 'la')
      ->distinct(TRUE);
    $sub_query->leftJoin('plan__plot', 'pp', 'la.asset_target_id = pp.plot_target_id');
    $sub_query->innerJoin('plan_field_data', 'pfd', 'pp.entity_id = pfd.id');

    // Add a field for both the log_id and the plan id.
    // The log_id alias is only used internally for the join condition.
    // We must alias the plan id as the "id" of the joined subquery so that
    // contextual filters can add a proper where condition.
    $sub_query->addField('la', 'entity_id', 'log_id');
    $sub_query->addField('pfd', 'id', 'id');

    // Add join condition.
    $condition = "log_field_data.id = %alias.log_id";
    $select_query->addJoin($this->type, $sub_query, $table['alias'], $condition);
  }

}
