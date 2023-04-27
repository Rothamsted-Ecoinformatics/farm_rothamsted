<?php

namespace Drupal\farm_rothamsted_experiment_research\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Proposal entity form class. */
class ProposalEntityForm extends ResearchEntityForm {

  /**
   * {@inheritdoc}
   */
  public function getTabDefinitions() {
    return [
      'description' => [
        'title' => $this->t('Description'),
        'weight' => 0,
        'fields' => [
          'name',
          'program',
          'contact',
          'experiment_category',
          'research_question',
        ],
      ],
      'design' => [
        'title' => $this->t('Statistical Design'),
        'weight' => 5,
        'fields' => [
          'crop',
          'treatment',
          'num_treatments',
          'num_replicates',
          'num_plots_total',
          'statistical_design',
          'measurements',
        ],
      ],
      'layout' => [
        'title' => $this->t('In-Field Layout'),
        'weight' => 7,
        'fields' => [
          'requested_location',
          'field_layout',
          'plot_length',
          'plot_width',
        ],
      ],
      'restriction' => [
        'title' => $this->t('Restrictions'),
        'weight' => 10,
        'fields' => [
          'restriction_crop',
          'restriction_crop_desc',
          'restriction_gm',
          'restriction_gm_desc',
          'restriction_ge',
          'restriction_ge_desc',
          'restriction_off_label',
          'restriction_off_label_desc',
          'restriction_licence_perm',
          'restriction_licence_perm_desc',
          'restriction_physical',
          'restriction_physical_desc',
          'restriction_other',
        ],
      ],
      'management' => [
        'title' => $this->t('Farm Management'),
        'weight' => 15,
        'fields' => [
          'experiment_management',
          'management_seed_supply',
          'management_seed_treatment',
          'management_pesticide',
          'management_nutrition',
          'management_harvest',
        ],
      ],
      'file' => [
        'title' => $this->t('Files'),
        'weight' => 20,
        'fields' => [
          'initial_quote',
          'file',
          'image',
          'link',
        ],
      ],
      'status' => [
        'title' => $this->t('Status'),
        'weight' => 25,
        'fields' => [
          'experiment',
          'design',
          'plan',
          'amendments',
          'reviewer',
          'status',
          'status_notes',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Hide restriction description fields until checked.
    $rotation_fields = [
      'restriction_crop',
      'restriction_gm',
      'restriction_ge',
      'restriction_off_label',
      'restriction_licence_perm',
      'restriction_physical',
    ];
    foreach ($rotation_fields as $field) {
      $form[$field . '_desc']['#states'] = [
        'visible' => [
          ':input[name="' . $field . '"]' => ['value' => 1],
        ],
      ];
    }

    return $form;
  }

}
