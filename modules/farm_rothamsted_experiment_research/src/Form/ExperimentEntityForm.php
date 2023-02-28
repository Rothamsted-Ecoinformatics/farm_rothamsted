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
          'objective',
        ],
      ],
      'rotation' => [
        'title' => $this->t('Rotation'),
        'weight' => 5,
        'fields' => [
          'rotation_treatment',
          'rotation_name',
          'rotation_description',
          'rotation_crops',
          'rotation_phasing',
          'rotation_notes',
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
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $rotation_fields = [
      'rotation_name',
      'rotation_description',
      'rotation_crops',
      'rotation_phasing',
      'rotation_notes',
    ];
    foreach ($rotation_fields as $field) {
      $form[$field]['#states'] = [
        'visible' => [
          ':input[name="rotation_treatment[value]"]' => ['checked' => TRUE],
        ],
      ];
    }

    return $form;
  }

}
