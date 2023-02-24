<?php

namespace Drupal\farm_rothamsted_experiment\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\plan\Entity\PlanInterface;

/**
 * Experiment plot geometry form.
 */
class ExperimentPlotGeometryForm extends ExperimentFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rothamsted_experiment_plot_geometry_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, PlanInterface $plan = NULL) {

    // Bail if no plan.
    if (empty($plan)) {
      return $form;
    }

    // Save the plan ID.
    $form['plan_id'] = [
      '#type' => 'hidden',
      '#value' => $plan->id(),
    ];

    // Ensure plots have been created.
    $has_plots = !$plan->get('plot')->isEmpty();
    if (!$has_plots) {
      $this->messenger()->addWarning($this->t('Create experiment plots before uploading plot geometry.'));
      return $this->redirect('farm_rothamsted_experiment.experiment_plot_form', ['plan' => $plan->id()]);
    }

    // Allow uploading a geojson.
    $plan_file_location = $this->getFileUploadLocation('plan', 'rothamsted_experiment', 'file');
    $form['geojson'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Plot geometries'),
      '#description' => $this->t('GeoJSON file containing each plot number and geometry.'),
      '#upload_validators' => [
        'file_validate_extensions' => ['geojson'],
      ],
      '#upload_location' => $plan_file_location,
      '#limit_validation_errors' => [],
    ];

    // Revision message.
    $form['revision_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Revision message'),
      '#description' => $this->t('Describe the reason for this change.'),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @todo Implement submitForm() method.
  }

}
