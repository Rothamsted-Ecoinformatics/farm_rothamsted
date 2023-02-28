<?php

namespace Drupal\farm_rothamsted_experiment_research\Form;

/**
 * Design entity form class.
 */
class DesignEntityForm extends ResearchEntityForm {

  /**
   * {@inheritdoc}
   */
  public function getTabDefinitions() {
    return [
      'description' => [
        'title' => $this->t('Description'),
        'weight' => 0,
        'fields' => [
          'experiment',
          'name',
          'description',
          'design_type',
          'start',
          'end',
          'statistician',
          'model',
        ],
      ],
      'treatment' => [
        'title' => $this->t('Treatments'),
        'weight' => 5,
        'fields' => [
          'treatment',
          'num_treatments',
          'num_factor_level_combinations',
          'num_replicates',
          'notes',
        ],
      ],
      'layout' => [
        'title' => $this->t('Layout'),
        'weight' => 10,
        'fields' => [
          'layout_description',
          'horizontal_row_spacing',
          'vertical_row_spacing',
          'plot_length',
          'plot_width',
          'plot_area',
          'total_plot_area',
          'experiment_area',
          'num_rows',
          'num_columns',
          'num_blocks',
          'num_plots_block',
          'num_mainplots',
          'num_subplots_mainplots',
          'num_subplots',
          'num_subsubplots_subplot',
          'num_subsubplots',
        ],
      ],
    ];
  }

}
