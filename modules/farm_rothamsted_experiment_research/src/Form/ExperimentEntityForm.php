<?php

namespace Drupal\farm_rothamsted_experiment_research\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Experiment entity form class. */
class ExperimentEntityForm extends ResearchEntityForm {

  /**
   * {@inheritdoc}
   */
  public function getTabDefinitions() {
    return [
      'description' => [
        'title' => $this->t('Description'),
        'weight' => 0,
        'fields' => [
          'program',
          'name',
          'code',
          'abbreviation',
          'description',
          'category',
          'start',
          'end',
          'researcher',
          'website',
        ],
      ],
      'permission' => [
        'title' => $this->t('Permissions'),
        'weight' => 10,
        'fields' => [
          'confidential_treatment',
          'data_license',
          'data_access',
          'data_access_notes',
          'public_release',
          'public_release_date',
        ],
      ],
      'file' => [
        'title' => $this->t('Files'),
        'weight' => 15,
        'fields' => [
          'file',
          'image',
          'link',
        ],
      ],
      'status' => [
        'title' => $this->t('Status'),
        'weight' => 20,
        'fields' => [
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

    // Hide public release date until checked.
    $form['public_release_date']['#states'] = [
      'visible' => [
        ':input[name="public_release[value]"]' => ['checked' => TRUE],
      ],
    ];

    return $form;
  }

}
