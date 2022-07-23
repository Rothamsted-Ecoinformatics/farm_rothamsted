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

        // Load field factors from json.
        $field_factors = json_decode($plan->get('column_descriptors')->value);

        // Build factor options for each factor type.
        $factor_options = [];
        foreach ($field_factors as $factor_type) {

          // Build options for each factor level.
          // Use a comma to separate each option as "type_id,level_id"
          // since commas are unlikely to be used in either ID.
          $factor_type_options = [];
          foreach ($factor_type->factor_levels as $factor_level) {
            $value = $factor_type->id . ',' . $factor_level->id;
            $factor_type_options[$value] = "$factor_level->name ($factor_level->id)";
          }

          // Build label for the factor type.
          $type_label = "$factor_type->name ($factor_type->id)";
          $factor_options[$type_label] = $factor_type_options;
        }

        // Set the value options.
        $this->valueOptions = $factor_options;
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
