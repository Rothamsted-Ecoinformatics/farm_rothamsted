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
    $revision_message = $form_state->getValue('revision_message');

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

    // Redirect to the plan variables page after processing.
    $form_state->setRedirect('view.rothamsted_experiment_plan_plots.page', ['plan' => $plan->id()]);

    $experiment_code = $plan->get('experiment_code')->value;
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $plot_field */
    $plot_count = $form_state->getValue('plot_count', 1);

    // Build batch operations to update plot geometries.
    $operations[] = [
      [self::class, 'updatePlotBatch'],
      [$plan->id(), $plot_count, $experiment_code, $boundary->id(), $revision_message],
    ];
    $batch = [
      'operations' => $operations,
      'title' => $this->t('Creating plots'),
      'progress_message' => $this->t('Creating plots'),
      'error_message' => $this->t('Error creating plots.'),
    ];
    batch_set($batch);
  }

  /**
   * Batch operation callback to update plot geometries.
   *
   * @param int $plan_id
   *   The plan ID.
   * @param int $plot_count
   *   The plot count.
   * @param string $experiment_code
   *   The experiment code.
   * @param int $boundary
   *   The experiment boundary for plot parent.
   * @param string $revision_message
   *   The revision message.
   * @param array $context
   *   The batch context.
   */
  public static function updatePlotBatch(int $plan_id, int $plot_count, string $experiment_code, int $boundary, string $revision_message, array &$context) {

    // Init the batch sandbox.
    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = $plot_count;
    }

    // Parameters for the size of the batch.
    $limit = 50;
    $current = $context['sandbox']['progress'] + 1;
    $end = min($current + $limit, $plot_count + 1);

    // Create plots.
    for ($i = $current; $i < $end; $i++) {

      // Create plot.
      $plot_name = "$experiment_code: $i";
      $plot = Asset::create([
        'type' => 'plot',
        'name' => $plot_name,
        'status' => 'active',
        'plot_type' => 'undefined',
        'plot_number' => $i,
        'plot_id' => $i,
        'is_fixed' => TRUE,
        'is_location' => FALSE,
        'parent' => $boundary,
        'column_descriptors' => [],
      ]);
      $plot->setRevisionLogMessage($revision_message);
      $plot->setNewRevision(TRUE);
      $plot->save();

      // Update sandbox.
      $context['sandbox']['progress']++;
      $context['sandbox']['current_id'] = $i;
      $context['results'][] = $plot->id();
      $context['message'] = \Drupal::translation()->formatPlural($context['sandbox']['progress'], 'Created @count plot.', 'Created @count plots.');
    }

    // Update finished progress.
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
    else {

      // Append all IDs to the plan.
      $plan = Plan::load($plan_id);
      $plan->set('plot', $context['results']);
      $plan->setRevisionLogMessage($revision_message);
      $plan->setNewRevision(TRUE);
      $plan->save();

      // Add success message.
      \Drupal::messenger()->addStatus(
        \Drupal::translation()->formatPlural($context['sandbox']['max'], 'Success. Created @count plot.', 'Success. Created @count plots.')
      );
    }
  }

}
