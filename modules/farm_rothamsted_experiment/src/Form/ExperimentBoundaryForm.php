<?php

namespace Drupal\farm_rothamsted_experiment\Form;

use Drupal\asset\Entity\Asset;
use Drupal\Core\Form\FormStateInterface;
use Drupal\plan\Entity\Plan;
use Drupal\plan\Entity\PlanInterface;

/**
 * Experiment boundary form.
 */
class ExperimentBoundaryForm extends ExperimentFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rothamsted_experiment_boundary_form';
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

    // Bail if the experiment already has a boundary.
    $boundary = $this->experimentBoundary($plan);
    if (!empty($boundary)) {
      $boundary_url = $boundary->toUrl()->setAbsolute()->toString();
      $this->messenger()->addWarning(
        $this->t(
          'The experiment %experiment already has a boundary: <a href="@boundary_url">%boundary</a>',
          [
            '%experiment' => $plan->label(),
            '@boundary_url' => $boundary_url,
            '%boundary' => $boundary->label(),
          ],
        ),
      );
      return $form;
    }

    // Ensure required fields are provided:
    $form['required_message'] = [
      '#type'  => 'html_tag',
      '#tag'   => 'p',
      '#value' => $this->t('The experiment code and experiment locations are required to create an experiment boundary. Verify that these are correct before creating the experiment boundary.'),
    ];

    // Experiment code.
    $form['experiment_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Experiment code'),
      '#description' => $this->t('The unique identifier for the study, for example 2020/R/CS/790.'),
      '#default_value' => $plan->get('experiment_code')->value,
      '#required' => TRUE,
    ];

    // Location for the experiment boundary parents.
    $default_locations = $plan->get('location')->referencedEntities();
    $form['location'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Experiment location'),
      '#description' => $this->t('The fields in which the experiment is located.'),
      '#target_type' => 'asset',
      '#selection_handler' => 'views',
      '#selection_settings' => [
        'view' => [
          'view_name' => 'rothamsted_quick_location_reference',
          'display_name' => 'entity_reference',
          'arguments' => [],
        ],
        'match_operator' => 'CONTAINS',
      ],
      '#tags' => TRUE,
      '#default_value' => $default_locations,
      '#required' => TRUE,
    ];

    // Revision message.
    $form['revision_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Revision message'),
      '#description' => $this->t('Describe the reason for this change.'),
      '#default_value' => 'Create experiment boundary.',
      '#required' => TRUE,
    ];

    // Add a submit button that handles the submission of the form.
    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Create boundary'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Get the plan.
    $plan = Plan::load($form_state->getValue('plan_id'));

    // Set the experiment code.
    $code = $form_state->getValue('experiment_code');
    $plan->set('experiment_code', $code);

    // Set the experiment location.
    $location = $form_state->getValue('location');
    $plan->set('location', $location);

    // Create and save land asset.
    $boundary = Asset::create([
      'type' => 'land',
      'land_type' => 'experiment_boundary',
      'name' => $this->t('@code (@plan_name): Experiment Boundary', ['@code' => $code, '@plan_name' => $plan->label()]),
      'status' => 'active',
      'parent' => $location,
      'is_fixed' => TRUE,
      'is_location' => TRUE,
    ]);
    $boundary->save();

    // Add land asset to the plan.
    $plan->get('asset')->appendItem($boundary);

    // Add message.
    $boundary_url = $boundary->toUrl()->setAbsolute()->toString();
    $this->messenger()->addStatus(
      $this->t(
        'Created experiment boundary: <a href="@boundary_url">%boundary</a>',
        [
          '@boundary_url' => $boundary_url,
          '%boundary' => $boundary->label(),
        ],
      ),
    );

    // Save the plan.
    $plan->setRevisionLogMessage($form_state->getValue('revision_message'));
    $plan->setNewRevision(TRUE);
    $plan->save();

    // Redirect to the plan page.
    $form_state->setRedirectUrl($plan->toUrl());
  }

}
