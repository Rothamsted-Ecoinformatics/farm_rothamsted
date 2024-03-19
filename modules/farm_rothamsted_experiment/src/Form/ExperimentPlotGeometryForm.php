<?php

namespace Drupal\farm_rothamsted_experiment\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\plan\Entity\Plan;
use Drupal\plan\Entity\PlanInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Experiment plot geometry form.
 */
class ExperimentPlotGeometryForm extends ExperimentFormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs new form.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rothamsted_experiment_plot_geometry_form';
  }

  /**
   * Access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\plan\Entity\PlanInterface $plan
   *   The plan entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, PlanInterface $plan) {
    return $plan->access('update', $account, TRUE)->andIf(AccessResult::allowedIfHasPermission($account, 'upload rothamsted_experiment plan geometries'));
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
      '#description' => $this->t('GeoJSON file containing each plot number, plot ID and geometry.'),
      '#upload_validators' => [
        'file_validate_extensions' => ['geojson'],
      ],
      '#upload_location' => $plan_file_location,
      '#limit_validation_errors' => [],
      '#required' => TRUE,
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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $geojson = $this->loadGeojson($form_state);

    // Special case when a file is uploaded.
    $trigger = $form_state->getTriggeringElement();
    if (empty($trigger['#array_parents']) || $trigger['#array_parents'][0] != 'geojson') {
      return;
    }

    // Do not validate when removing a file.
    if ($trigger['#array_parents'][1] === 'remove_button') {
      return;
    }

    // Validate geojson and features.
    if (empty($geojson)) {
      $error_msg = 'Could not parse GeoJSON.';
      $form_state->setError($form['geojson'], $error_msg);
      $this->messenger()->addError($error_msg);
      return;
    }
    $features = $geojson['features'] ?? [];
    if (empty($features)) {
      $error_msg = 'No features found in GeoJSON.';
      $form_state->setError($form['geojson'], $error_msg);
      $this->messenger()->addError($error_msg);
      return;
    }

    // Build mapping of plot IDs keyed by plot number.
    $plot_mapping = [];
    foreach ($features as $feature) {
      $plot_number = $feature['properties']['plot_number'] ?? NULL;
      $plot_id = $feature['properties']['plot_id'] ?? NULL;
      if (!$plot_number || !$plot_id) {
        $error_msg = "Feature missing plot_number or plot_id. Ensure all features have correct plot_number and plot_id properties.";
        $form_state->setError($form['geojson'], $error_msg);
        $this->messenger()->addError($error_msg);
        return;
      }
      $plot_mapping[(int) $plot_number] = $plot_id;
    }
    ksort($plot_mapping);

    // Validate plot count.
    $plot_numbers = array_keys($plot_mapping);
    $expected_total = count($plot_numbers);
    $expected_numbers = range(1, $expected_total);

    // Validate expected plot numbers.
    $diff = array_diff($expected_numbers, $plot_numbers);
    if (!empty($diff)) {
      $missing_count = count($diff);
      $error_msg = "Missing $missing_count plot numbers. Ensure plot numbers start at one and continue through the total number of plots.";
      $form_state->setError($form['geojson'], $error_msg);
      $this->messenger()->addError($error_msg);
    }

    // Validate the total and sequential of existing plot numbers.
    // Subquery of plot IDs associated with the plan.
    $plan_plot_query = \Drupal::database()->select('plan__plot', 'pp')
      ->distinct(TRUE)
      ->condition('pp.entity_id', $form_state->getValue('plan_id'))
      ->condition('pp.deleted', 0);
    $plan_plot_query->addField('pp', 'plot_target_id', 'plot_id');
    $existing_plot_query = \Drupal::database()->select('asset__plot_number', 'apm')
      ->condition('apm.entity_id', $plan_plot_query, 'IN')
      ->condition('apm.deleted', 0);
    $existing_plot_query->join('asset__plot_id', 'api', 'api.entity_id = apm.entity_id AND api.deleted = 0');
    $existing_plot_query->addField('apm', 'plot_number_value', 'plot_number');
    $existing_plot_query->addField('api', 'plot_id_value', 'plot_id');
    $existing_plot_query->orderBy('plot_number');
    // Fetch existing plot ids keyed by plot number, sorted by plot.
    $existing_plots = $existing_plot_query->execute()->fetchAllKeyed();

    // Validate total numbers.
    $total_existing_plot = count($existing_plots);
    if ((int) $total_existing_plot != $expected_total) {
      $error_msg = "Total number of plots in GeoJSON does not match number of existing plots created for this plan. GeoJSON plots: $expected_total Existing: $total_existing_plot";
      $form_state->setError($form['geojson'], $error_msg);
      $this->messenger()->addError($error_msg);
      return;
    }

    // Validate that existing plot numbers are sequential with the GeoJSON.
    $plot_numbers = array_keys($existing_plots);
    $diff = array_diff($expected_numbers, $plot_numbers);
    if (!empty($diff)) {
      $missing_count = count($diff);
      $error_msg = "The existing plot numbers do not match with those in the GeoJSON. Missing $missing_count plots.";
      $form_state->setError($form['plot_attributes'], $error_msg);
      $this->messenger()->addError($error_msg);
    }

    // Validate that plot numbers and plot IDs match with existing plots.
    if ($plot_mapping !== $existing_plots) {
      $diff = array_diff($plot_mapping, $existing_plots);
      $plot_number = array_key_first($diff);
      $plot_id = reset($diff);
      $error_msg = "Mismatched plot_number and plot_id: $plot_number - $plot_id";
      $form_state->setError($form['geojson'], $error_msg);
      $this->messenger()->addError($error_msg);
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Get the plan.
    $plan = Plan::load($form_state->getValue('plan_id'));
    $revision_message = $form_state->getValue('revision_message');

    // Add the geojson file to the plan.
    if ($file_ids = $form_state->getValue('geojson')) {
      $file = $this->entityTypeManager->getStorage('file')->load(reset($file_ids));
      $plan->get('file')->appendItem($file);
      $plan->save();
    }

    $geojson = $this->loadGeojson($form_state);
    $features = $geojson['features'] ?? [];

    $plot_numbers = array_map(function ($feature) {
      return (int) $feature['properties']['plot_number'] ?? NULL;
    }, $features);
    $plot_features = array_combine($plot_numbers, $features);

    // Redirect to the plan variables page after processing.
    $form_state->setRedirect('view.rothamsted_experiment_plan_plots.page', ['plan' => $plan->id()]);

    // Build batch operations to update plot geometries.
    $operations[] = [
      [self::class, 'updatePlotBatch'],
      [$plan->id(), $plot_features, $revision_message],
    ];
    $batch = [
      'operations' => $operations,
      'title' => $this->t('Updating plot geometries'),
      'progress_message' => $this->t('Updating plot geometries'),
      'error_message' => $this->t('Error updating plot geometries.'),
    ];
    batch_set($batch);
  }

  /**
   * Batch operation callback to update plot geometries.
   *
   * @param int $plan_id
   *   The plan ID.
   * @param array $plot_features
   *   The plot features.
   * @param string $revision_message
   *   The revision message.
   * @param array $context
   *   The batch context.
   */
  public static function updatePlotBatch(int $plan_id, array $plot_features, string $revision_message, array &$context) {

    // Init the batch sandbox.
    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['current_id'] = 0;
      $context['sandbox']['max'] = count($plot_features);
    }

    // Parameters for the size of the batch.
    $limit = 50;
    $current = $context['sandbox']['current_id'];

    // Subquery of plot IDs associated with the plan.
    $plan_plot_query = \Drupal::database()->select('plan__plot', 'pp')
      ->distinct(TRUE)
      ->condition('pp.entity_id', $plan_id)
      ->condition('pp.deleted', 0);
    $plan_plot_query->addField('pp', 'plot_target_id', 'plot_id');

    // Query plots to update in this batch.
    $asset_storage = \Drupal::entityTypeManager()->getStorage('asset');
    $plot_ids = $asset_storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'plot')
      ->condition('status', 'active')
      ->condition('id', $plan_plot_query, 'IN')
      ->condition('plot_number', $current, '>')
      ->range(0, $limit)
      ->sort('plot_number')
      ->execute();

    // Iterate over plots and update values.
    $geo_php = \Drupal::service('geofield.geophp');
    /** @var \Drupal\asset\Entity\AssetInterface[] $plots */
    $plots = $asset_storage->loadMultiple($plot_ids);
    foreach ($plots as $plot) {

      // Get the plot number to match with the plot feature.
      $plot_number = (int) $plot->get('plot_number')->value;
      if (!isset($plot_features[$plot_number])) {
        continue;
      }

      // Extract the intrinsic geometry references.
      // Re-encode into json.
      $featureJson = Json::encode($plot_features[$plot_number] ?? []);
      $wkt = $geo_php->load($featureJson, 'json')->out('wkt');

      // Save the plot.
      $plot->set('intrinsic_geometry', $wkt);
      $plot->setNewRevision(TRUE);
      $plot->setRevisionLogMessage($revision_message);
      $plot->save();

      // Update sandbox.
      $context['sandbox']['progress']++;
      $context['sandbox']['current_id'] = $plot_number;
      $context['message'] = \Drupal::translation()->formatPlural($plot_number, 'Updated @count plot.', 'Updated @count plots.');
    }

    // Update finished progress.
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
    // Add success message.
    else {
      \Drupal::messenger()->addStatus(
        \Drupal::translation()->formatPlural($context['sandbox']['max'], 'Success. Updated @count plot.', 'Success. Updated @count plots.')
      );
    }
  }

  /**
   * Helper function to load and parse uploaded geojson.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   An array of parsed geojson data.
   */
  protected function loadGeojson(FormStateInterface $form_state) {

    $data = [];
    $form_key = 'geojson';
    if ($file_ids = $form_state->getValue($form_key)) {

      // Load the file entity.
      $file = $this->entityTypeManager->getStorage('file')->load(reset($file_ids));

      // Get file contents and convert the json to php arrays.
      $file_data = file_get_contents($file->getFileUri());
      $data = Json::decode($file_data);
    }

    return $data;
  }

}
