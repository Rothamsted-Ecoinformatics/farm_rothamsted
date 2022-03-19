<?php

namespace Drupal\farm_rothamsted_experiment\Plugin\views\filter;

use Drupal\plan\Entity\Plan;
use Drupal\views\Plugin\views\filter\ManyToOne;

/**
 * Custom views filter for the plot factor levels.
 *
 * Reads factor levels from the plan specified by the views contextual filter.
 *
 * @ViewsFilter("factor_levels")
 */
class FactorLevel extends ManyToOne {

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
      if (!empty($plan) && $plan->hasField('field_factors') && !$plan->get('field_factors')->isEmpty()) {

        // Load field factors from json.
        $field_factors = json_decode($plan->get('field_factors')->value);

        // Build factor options for each factor type.
        $factor_options = [];
        foreach ($field_factors as $factor_type) {

          // Build label for each factor level.
          $factor_type_options = [];
          foreach ($factor_type->factor_levels as $factor_level) {
            $factor_type_options[$factor_level->id] = "$factor_level->name ($factor_level->id)";
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

}
