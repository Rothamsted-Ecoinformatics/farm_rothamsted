<?php

namespace Drupal\farm_rothamsted_experiment\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Custom views filter for the plot column levels.
 *
 * @see farm_rothamsted_experiment_views_pre_view
 *
 * @ViewsFilter("column_level")
 */
class ColumnLevel extends InOperator {

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    parent::buildExposedForm($form, $form_state);

    // Set the element title. The parent function removes it.
    $value = $this->options['expose']['identifier'];
    $form[$value]['#title'] = $this->exposedInfo()['label'];
    $form[$value]['#group'] = 'column_descriptors';
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {

    // Same logic as parent method.
    if (isset($this->valueOptions)) {
      return $this->valueOptions;
    }

    // Populate value options from filter settings.
    $this->valueOptions = [];
    if ($options = $this->options['settings']['column_options']) {
      $this->valueOptions = $options;
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

    // Build a custom join instead of using $this->ensureMyTable().
    // This lets us do a left outer join on the column_descriptor_key column.
    // This is much more efficient if multiple column descriptors are actively
    // being filtered.
    $column_id = $this->options['settings']['column_id'];
    $join = $this->getJoin();
    $join->extra = [
      [
        'field' => 'column_descriptors_key',
        'value' => $column_id,
      ],
    ];
    $this->tableAlias = $this->query->addTable($this->table, $this->relationship, $join);

    // Add a where expression for the column descriptor value.
    $value_placeholder = $this->placeholder() . '[]';
    $this->query->addWhereExpression(NULL, "$this->tableAlias.column_descriptors_value IN($value_placeholder)", [$value_placeholder => $this->value]);
  }

}
