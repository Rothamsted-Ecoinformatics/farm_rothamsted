<?php

namespace Drupal\farm_rothamsted_experiment_research\Form;

use Drupal\Core\Form\FormStateInterface;

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
          'blocking_structure',
          'statistical_design',
          'blocking_constraint',
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

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Add ajax.
    $form['statistical_design']['#attributes']['id'] = 'statistical-design-wrapper';
    $form['blocking_structure']['widget']['#ajax'] = [
      'callback' => [$this, 'blockingStructureCallback'],
      'wrapper' => 'statistical-design-wrapper',
      'event' => 'change',
    ];

    // Build options.
    $blocking_structure = $form_state->getValue(['blocking_structure', 0, 'value']) ?? $this->entity->get('blocking_structure')->value;
    $options = farm_rothamsted_experiment_research_statistical_design_options($blocking_structure);
    $form['statistical_design']['widget']['#options'] = $options;

    return $form;
  }

  /**
   * AJAX callback for blocking structure and design.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form element to replace.
   */
  public function blockingStructureCallback(array &$form, FormStateInterface $form_state) {
    return $form['statistical_design'];
  }

}
