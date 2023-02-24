<?php

namespace Drupal\farm_rothamsted_experiment\Form;

use Drupal\asset\Entity\Asset;
use Drupal\Core\Form\FormStateInterface;
use Drupal\plan\Entity\Plan;
use Drupal\plan\Entity\PlanInterface;

/**
 * Experiment plot form.
 */
class ExperimentPlotForm extends ExperimentFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rothamsted_experiment_plot_form';
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

    // Ensure experiment boundary exists.
    $boundary = $this->experimentBoundary($plan);
    if (empty($boundary)) {
      $this->messenger()->addWarning($this->t('An experiment boundary is required before you can create plots.'));
      return $this->redirect('farm_rothamsted_experiment.experiment_boundary_form', ['plan' => $plan->id()]);
    }

    // Enter the number of plots to create.
    $form['plot_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of plots'),
      '#description' => $this->t('Enter the number of plots to create for this experiment plan. This cannot be changed after plots are created.'),
      '#min' => 1,
      '#step' => 1,
      '#required' => TRUE,
    ];

    // Disable if the experiment already has plots.
    $has_plots = !$plan->get('plot')->isEmpty();
    if ($has_plots) {
      $this->messenger()->addError(
        $this->t(
          'The experiment %experiment already has plots that cannot be added or removed.',
          [
            '%experiment' => $plan->label(),
          ],
        ),
      );
    }

    // Revision message.
    $plan_label = $plan->label();
    $form['revision_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Revision message'),
      '#description' => $this->t('Describe the reason for this change.'),
      '#default_value' => "Create $plan_label plots.",
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Create plots'),
        '#disabled' => $has_plots,
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

    // Bail if no boundary.
    $boundary = $this->experimentBoundary($plan);
    if (empty($boundary)) {
      return $form;
    }

    // Bail if already has plots.
    $has_plots = !$plan->get('plot')->isEmpty();
    if ($has_plots) {
      return $form;
    }

    // Create plots.
    $experiment_code = $plan->get('experiment_code')->value;
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $plot_field */
    $plot_field = $plan->get('plot');
    $plot_count = $form_state->getValue('plot_count', 1);
    foreach (range(1, $plot_count) as $plot_number) {

      // Create plot.
      $plot_name = "$experiment_code: $plot_number";
      $plot = Asset::create([
        'type' => 'plot',
        'name' => $plot_name,
        'status' => 'active',
        'plot_type' => 'blank',
        'plot_number' => $plot_number,
        'plot_id' => $plot_number,
        'is_fixed' => TRUE,
        'is_location' => FALSE,
        'parent' => $boundary,
        'column_descriptors' => [],
      ]);
      $plot->setRevisionLogMessage($form_state->getValue('revision_message'));
      $plot->setNewRevision(TRUE);
      $plot->save();

      // Append plot to plan.
      $plot_field->appendItem($plot);
    }

    // Save the plan.
    $plan->setRevisionLogMessage($form_state->getValue('revision_message'));
    $plan->setNewRevision(TRUE);
    $plan->save();

    // Add message.
    $this->messenger()->addMessage($this->t('Created @count plots.', ['@count' => $plot_count]));

    // Redirect to the plan page.
    $form_state->setRedirectUrl($plan->toUrl());
  }

}
