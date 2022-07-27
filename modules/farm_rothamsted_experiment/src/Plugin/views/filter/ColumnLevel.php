<?php

namespace Drupal\farm_rothamsted_experiment\Plugin\views\filter;

use Drupal\plan\Entity\Plan;
use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Custom views filter for the plot column levels.
 *
 * Reads column levels from the plan specified by the views contextual filter.
 *
 * @ViewsFilter("column_level")
 */
class ColumnLevel extends InOperator {

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {

    // Same logic as parent method.
    if (isset($this->valueOptions)) {
      return $this->valueOptions;
    }

    // Check if a plan id is provided.
    // @todo Should the plan argument or id be configuration for the filter?
    if (isset($this->view->args[0])) {

      // Load the plan.
      $plan_id = $this->view->args[0];
      $plan = Plan::load($plan_id);
      if (!empty($plan) && $plan->hasField('column_descriptors') && !$plan->get('column_descriptors')->isEmpty()) {

        // Load columns from json.
        $columns = json_decode($plan->get('column_descriptors')->value);

        // Build options for each column.
        $column_options = [];
        foreach ($columns as $column) {

          // Build options for each column level.
          // Use a comma to separate each option as "column_id,level_id"
          // since commas are unlikely to be used in either ID.
          $column_level_options = [];
          foreach ($column->column_levels as $column_level) {
            $value = $column->column_id . ',' . $column_level->level_id;
            $column_level_options[$value] = "$column_level->level_name ($column_level->level_id)";
          }

          // Build label for the column.
          $type_label = "$column->column_name ($column->column_id)";
          $column_options[$type_label] = $column_level_options;
        }

        // Set the value options.
        $this->valueOptions = $column_options;
      }
    }
    // If no plan is provided, default to no options.
    else {
      $this->valueOptions = [];
    }

    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  protected function opSimple() {
    if (empty($this->value)) {
      return;
    }
    $this->ensureMyTable();

    // Create an additional OR group.
    $group = count($this->query->where) + 1;
    $this->query->setWhereGroup('OR', $group);

    // Add a where expression for each factor key and value.
    foreach ($this->value as $value) {
      $values = explode(',', $value);
      $key_placeholder = $this->placeholder();
      $value_placeholder = $this->placeholder();
      $this->query->addWhereExpression($group, "$this->tableAlias.column_descriptors_key = $key_placeholder AND $this->tableAlias.column_descriptors_value = $value_placeholder", [$key_placeholder => $values[0], $value_placeholder => $values[1]]);
    }
  }

}
