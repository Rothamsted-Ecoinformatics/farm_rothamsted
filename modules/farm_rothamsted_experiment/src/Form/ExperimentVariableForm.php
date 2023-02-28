<?php

namespace Drupal\farm_rothamsted_experiment\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\plan\Entity\Plan;
use Drupal\plan\Entity\PlanInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for uploading experiment variables.
 */
class ExperimentVariableForm extends ExperimentFormBase {

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
    return 'rothamsted_experiment_variable_form';
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
      $this->messenger()->addWarning($this->t('Create experiment plots before uploading experiment variables.'));
      return $this->redirect('farm_rothamsted_experiment.experiment_plot_form', ['plan' => $plan->id()]);
    }

    // Add language for creating or updating variables.

    // Add file upload fields.
    // Require all 3 CSV files to be uploaded.
    $plan_file_location = $this->getFileUploadLocation('plan', 'rothamsted_experiment', 'file');
    $form['column_descriptors'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Column descriptors'),
      '#description' => $this->t('CSV file containing the column descriptor definitions.'),
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
      '#upload_location' => $plan_file_location,
      '#limit_validation_errors' => [],
    ];
    $form['column_levels'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Column Levels'),
      '#description' => $this->t('CSV file containing the column level definitions for each column descriptor.'),
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
      '#upload_location' => $plan_file_location,
      '#limit_validation_errors' => [],
    ];

    $form['plot_attributes'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Plot attributes'),
      '#description' => $this->t('CSV file containing each plot number, id, type and column assignments.'),
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
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
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // List of file names and files to validate.
    $file_validate = [];
    $file_names = [
      'column_descriptors',
      'column_levels',
      'plot_attributes',
    ];

    // Load all the file data for convenience.
    $file_data = $this->loadFiles($form_state);

    // Special case when a file is uploaded.
    $trigger = $form_state->getTriggeringElement();
    if (!empty($trigger['#array_parents']) && in_array($trigger['#array_parents'][0], $file_names)) {

      // Do not validate when removing a file.
      if ($trigger['#array_parents'][1] === 'remove_button') {
        return;
      }

      // Ensure necessary files are uploaded.
      $uploaded_file = $trigger['#array_parents'][0];
      $uploaded_index = array_search($uploaded_file, $file_names);
      foreach ($file_names as $index => $file_name) {
        if ($index < $uploaded_index) {
          if (empty($file_data[$file_name])) {
            $form_state->setError($form[$uploaded_file], $this->t('%file_name must be uploaded first.', ['%file_name' => $file_name]));
            return;
          }
        }
      }

      // Ensure the file was parsed.
      if (empty($file_data[$uploaded_file])) {
        $form_state->setError($form[$uploaded_file], $this->t('Failed to parse %file_name.', ['%file_name' => $uploaded_file]));
        return;
      }

      // Add the file for custom validation.
      $file_validate[] = $uploaded_file;
    }

    // Validate all files again on form submission.
    if (!empty($trigger['#array_parents']) && $trigger['#parents'][0] == 'submit') {
      $file_validate = $file_names;
    }

    // Perform custom validation for each file as needed.
    foreach ($file_validate as $file_name) {

      // Defer to the file validation function.
      $function_name = 'validateFile' . str_replace('_', '', ucwords($file_name, '_'));
      $this->{$function_name}($file_data, $form, $form_state);
    }
  }

  /**
   * Validation function for the column descriptors file.
   *
   * @param array $file_data
   *   Processed file data.
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateFileColumnDescriptors(array $file_data, array &$form, FormStateInterface $form_state) {

    // List of required columns.
    $required_columns = [
      'column_type',
      'column_id',
      'column_name',
      'ontology_name',
      'length',
      'ontology_description',
      'ontology_uri',
      'data_type',
    ];

    // List of allowed column types.
    $allowed_column_types = [
      'design_factor',
      'field_attribute',
      'treatment_factor',
      'treatment_component',
      'treatment_application',
      'basal_treatment',
    ];

    // Validate each factor.
    $factors = $file_data['column_descriptors'];
    foreach ($factors as $row => $factor) {
      $row++;

      // Ensure all required values are provided.
      foreach ($required_columns as $column_name) {
        if (!isset($factor[$column_name]) || strlen($factor[$column_name]) === 0) {
          $error_msg = "Column in row $row is missing a $column_name";
          $form_state->setError($form['column_descriptors'], $error_msg);
          $this->messenger()->addError($error_msg);
        }
      }

      // Ensure allowed column type.
      $column_type = $factor['column_type'] ?? '';
      if (!in_array($column_type, $allowed_column_types)) {
        $error_msg = "Column in row $row has invalid column type: $column_type";
        $form_state->setError($form['column_descriptors'], $error_msg);
        $this->messenger()->addError($error_msg);
      }
    }
  }

  /**
   * Validation function for the column levels file.
   *
   * @param array $file_data
   *   Processed file data.
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateFileColumnLevels(array $file_data, array &$form, FormStateInterface $form_state) {

    // Get data from files.
    $levels = $file_data['column_levels'];
    $column_ids = array_column($file_data['column_descriptors'], 'column_id');
    $column_names = array_column($file_data['column_descriptors'], 'column_name');

    // Ensure all required values are provided.
    $required_columns = [
      'column_id',
      'level_id',
      'level_name',
    ];
    foreach ($levels as $row => $level) {
      $row++;
      foreach ($required_columns as $column_name) {
        if (!isset($level[$column_name]) || strlen($level[$column_name]) === 0) {
          $error_msg = "Column level in row $row is missing a $column_name";
          $form_state->setError($form['column_levels'], $error_msg);
          $this->messenger()->addError($error_msg);
        }

        // Ensure column_id and column_name is defined in column_descriptors.
        foreach (['column_id' => $column_ids, 'column_name' => $column_names] as $key => $allowed_values) {

          // The column_name is optional.
          if (!isset($level[$key])) {
            continue;
          }

          // Ensure the value is allowed.
          if (!in_array($level[$key], $allowed_values)) {
            $error_msg = "Column level in row $row has an invalid $key: $level[$key]";
            $form_state->setError($form['column_levels'], $error_msg);
            $this->messenger()->addError($error_msg);
          }
        }

        // Ensure the level_id is numeric and within the column length.
        // @todo Check that level_id is less than the column length.
        if (!is_numeric($level['level_id'])) {
          $error_msg = "Column level in row $row has an invalid $key: $level[$key]";
          $form_state->setError($form['column_levels'], $error_msg);
          $this->messenger()->addError($error_msg);
        }
      }
    }

    // Ensure there are as many column levels as each column length specifies.
    foreach ($file_data['column_descriptors'] as $column_descriptor) {

      // Get count of column levels.
      $column_id = $column_descriptor['column_id'];
      $column_levels = array_filter($levels, function ($level) use ($column_id) {
        return $level['column_id'] == $column_id;
      });
      $column_count = count($column_levels);
      $expected_length = $column_descriptor['length'];

      // Check lengths.
      if ($expected_length != $column_count) {
        $error_msg = "Incorrect number of column levels for column $column_id. Got $column_count expected $expected_length.";
        $form_state->setError($form['column_levels'], $error_msg);
        $this->messenger()->addError($error_msg);
      }
    }
  }

  /**
   * Validation function for the plot attribute csv file.
   *
   * @param array $file_data
   *   Processed file data.
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateFilePlotAttributes(array $file_data, array &$form, FormStateInterface $form_state) {

    // Get data from files.
    $plot_attributes = $file_data['plot_attributes'];

    // Build a column id -> level id map for validation plot attributes.
    // This makes it efficient to validate plot column id + level id pairs.
    $column_ids = array_column($file_data['column_descriptors'], 'column_id');
    $column_levels_map = array_fill_keys($column_ids, []);
    foreach ($file_data['column_levels'] as $column_level) {
      $column_id = $column_level['column_id'];
      $column_levels_map[$column_id][] = $column_level['level_id'];
    }

    // Keep track of plot 1.
    $has_plot_1 = FALSE;

    // Ensure all required values are provided.
    $valid_plot_types = array_keys(farm_rothamsted_experiment_plot_type_options());
    $required_columns = [
      'plot_number' => 'numeric',
      'plot_id' => 'string',
      'plot_type' => 'string',
      'row' => 'numeric',
      'column' => 'numeric',
    ];
    foreach ($plot_attributes as $row => $attributes) {

      // Increment row so count starts at 1 in error messages.
      $row++;

      // Ensure each that each plot has valid column_ids and level_names.
      foreach ($attributes as $column_name => $column_value) {

        // Check required columns.
        if (in_array($column_name, array_keys($required_columns))) {

          if (!isset($attributes[$column_name]) || strlen($attributes[$column_name]) === 0) {
            $error_msg = "Plot in row $row is missing a $column_name";
            $form_state->setError($form['plot_attributes'], $error_msg);
            $this->messenger()->addError($error_msg);
            continue;
          }

          // Check for numeric value type.
          if ($required_columns[$column_name] == 'numeric' && !is_numeric($column_value)) {
            $error_msg = "Invalid value for plot in row $row: $column_name is not numeric: $column_value";
            $form_state->setError($form['plot_attributes'], $error_msg);
            $this->messenger()->addError($error_msg);
            continue;
          }

          // Check for valid plot_type.
          if ($column_name == 'plot_type' && !in_array($column_value, $valid_plot_types)) {
            $error_msg = "Invalid plot type in row $row: $column_value";
            $form_state->setError($form['plot_attributes'], $error_msg);
            $this->messenger()->addError($error_msg);
            continue;
          }

          // Mark if we found plot number 1.
          if ($column_name == 'plot_number' && (int) $attributes[$column_name] == 1) {
            $has_plot_1 = TRUE;
          }

          // No further validation for required values.
          continue;
        }

        // Ensure each column is a valid column_id.
        if (!in_array($column_name, $column_ids)) {
          $error_msg = "Plot in row $row has an invalid column_id: $column_name";
          $form_state->setError($form['plot_attributes'], $error_msg);
          $this->messenger()->addError($error_msg);
          continue;
        }

        // Ensure each column_value is allowed for the column_name.
        // There should be a treatment factor level that has a matching
        // column_id and level_id.
        $matching_factor_level = isset($column_levels_map[$column_name]) && in_array($column_value, $column_levels_map[$column_name]);
        if ($column_value != 'na' && !$matching_factor_level) {
          $error_msg = "Plot in row $row has an invalid level_id for column_id '$column_name': $column_value";
          $form_state->setError($form['plot_attributes'], $error_msg);
          $this->messenger()->addError($error_msg);
        }
      }
    }

    // Ensure we found plot_number 1.
    if (!$has_plot_1) {
      $error_msg = 'Missing plot_number 1. Make sure the plot number starts at 1.';
      $form_state->setError($form['plot_attributes'], $error_msg);
      $this->messenger()->addError($error_msg);
      return;
    }

    // Validate plot numbers.
    $plot_numbers = array_column($plot_attributes, 'plot_number');
    $expected_total = count($plot_attributes);
    $expected_numbers = range(1, $expected_total);
    $diff = array_diff($expected_numbers, $plot_numbers);
    if (!empty($diff)) {
      $missing_count = count($diff);
      $error_msg = "Missing $missing_count plot numbers. Ensure plot numbers start at one and continue through the total number of plots.";
      $form_state->setError($form['plot_attributes'], $error_msg);
      $this->messenger()->addError($error_msg);
      return;
    }

    // Validate the total and sequential of existing plot numbers.
    // Subquery of plot IDs associated with the plan.
    $plan_plot_query = \Drupal::database()->select('plan__plot', 'pp')
      ->distinct(TRUE)
      ->condition('pp.entity_id', $form_state->getValue('plan_id'))
      ->condition('pp.deleted', 0);
    $plan_plot_query->addField('pp', 'plot_target_id', 'plot_id');
    $plot_number_query = \Drupal::database()->select('asset__plot_number', 'apm')
      ->condition('apm.entity_id', $plan_plot_query, 'IN')
      ->condition('apm.deleted', 0)
      ->orderBy('apm.plot_number_value');
    $plot_number_query->addField('apm', 'plot_number_value', 'plot_number');

    // Validate total numbers.
    $total_existing_plot = $plan_plot_query->countQuery()->execute()->fetchField();
    if ((int) $total_existing_plot != $expected_total) {
      $error_msg = "Total number of plots in CSV does not match number of existing plots created for this plan. CSV plots: $expected_total Existing: $total_existing_plot";
      $form_state->setError($form['plot_attributes'], $error_msg);
      $this->messenger()->addError($error_msg);
      return;
    }

    // Validate that existing plot numbers are sequential with the CSV.
    $plot_numbers = $plot_number_query->execute()->fetchCol();
    $diff = array_diff($expected_numbers, $plot_numbers);
    if (!empty($diff)) {
      $missing_count = count($diff);
      $error_msg = "The existing plot numbers do not match with those in the CSV. Missing $missing_count plots.";
      $form_state->setError($form['plot_attributes'], $error_msg);
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

    // Parse uploaded files.
    $file_data = $this->loadFiles($form_state);
    $column_descriptors = $file_data['column_descriptors'];
    $column_levels = $file_data['column_levels'];
    $plot_attributes_mapping = $file_data['plot_attributes'] ?? [];

    // Index by plot_number.
    $plot_attributes_mapping = array_combine(array_column($plot_attributes_mapping, 'plot_number'), $plot_attributes_mapping);

    // Build the plan column descriptors JSON for plan.column_descriptors.
    $plan_column_descriptors = [];

    // First add columns to plan_factors.
    foreach ($column_descriptors as $column) {
      $id = $column['column_id'];
      $plan_column_descriptors[$id] = $column + ['column_levels' => []];
    }

    // Add column levels.
    foreach ($column_levels as $column_level) {
      $id = $column_level['column_id'];
      $plan_column_descriptors[$id]['column_levels'][] = $column_level;
    }

    // Save the column descriptors.
    $plan->set(
      'column_descriptors',
      json_encode(array_values($plan_column_descriptors), JSON_INVALID_UTF8_SUBSTITUTE),
    );

    // Save uploaded files.
    $files = [
      'column_descriptors',
      'column_levels',
      'plot_attributes',
    ];
    foreach ($files as $form_key) {
      if ($file_ids = $form_state->getValue($form_key)) {
        $plan->get('file')->appendItem(reset($file_ids));
      }
    }

    // Finally, save the plan.
    $plan->setNewRevision(TRUE);
    $plan->setRevisionLogMessage($revision_message);
    $plan->save();

    // Redirect to the plan variables page after processing.
    $form_state->setRedirect('farm_rothamsted_experiment.plan.variables', ['plan' => $plan->id()]);

    // Build batch operations to update plot attributes.
    $experiment_code = $plan->get('experiment_code')->value;
    $operations[] = [
      [self::class, 'updatePlotBatch'],
      [$plan->id(), $experiment_code, $plot_attributes_mapping, $revision_message],
    ];
    $batch = [
      'operations' => $operations,
      'title' => $this->t('Updating plot attributes'),
      'progress_message' => $this->t('Updating plot attributes'),
      'error_message' => $this->t('Error updating plot attributes.'),
    ];
    batch_set($batch);
  }

  /**
   * Batch operation callback to update plots.
   *
   * @param int $plan_id
   *   The plan ID.
   * @param string $experiment_code
   *   The experiment code for the plot name.
   * @param array $plot_data
   *   Plot data to update on existing plots.
   * @param string $revision_message
   *   The revision message.
   * @param array $context
   *   The batch context.
   */
  public static function updatePlotBatch(int $plan_id, string $experiment_code, array $plot_data, string $revision_message, array &$context) {

    // Init the batch sandbox.
    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['current_id'] = 0;
      $context['sandbox']['max'] = count($plot_data);
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
      ->condition('type', 'plot')
      ->condition('status', 'active')
      ->condition('id', $plan_plot_query, 'IN')
      ->condition('plot_number', $current, '>')
      ->range(0, $limit)
      ->sort('plot_number')
      ->execute();

    // Iterate over plots and update values.
    /** @var \Drupal\asset\Entity\AssetInterface[] $plots */
    $plots = $asset_storage->loadMultiple($plot_ids);
    foreach ($plots as $plot) {

      // Get the plot number to match with the plot data.
      $plot_number = (int) $plot->get('plot_number')->value;
      $plot_attributes = $plot_data[$plot_number];
      if (empty($plot_attributes)) {
        continue;
      }

      // Build the plot name from the feature data.
      $plot_id = $plot_attributes['plot_id'];
      $plot->set('name', "$experiment_code: $plot_id");
      $plot->set('status', 'active');

      // Build column descriptors for the plot.
      $column_descriptors = [];

      // Assign plot field values.
      $normal_fields = [
        'plot_number',
        'plot_id',
        'plot_type',
        'row',
        'column',
      ];
      foreach ($plot_attributes as $column_name => $column_value) {

        // Map the normal fields to the plot asset field.
        if (in_array($column_name, $normal_fields)) {
          $plot->set($column_name, $column_value);
        }
        // Else the column is a factor key/value pair.
        // Don't include if the value is na.
        elseif ($column_value != 'na') {
          $column_descriptors[] = ['key' => $column_name, 'value' => $column_value];
        }
      }

      // Update plot column_descriptors.
      $plot->set('column_descriptors', $column_descriptors);

      // Save the plot.
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
   * Helper function to load and parse uploaded files.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   An array of parsed file data keyed by form key.
   */
  protected function loadFiles(FormStateInterface $form_state) {

    // Start an array of file data.
    $data = [];

    // Load each file and parse out the data.
    $files = ['column_descriptors', 'column_levels', 'plot_attributes'];
    foreach ($files as $form_key) {

      // Get id of the submitted file.
      if ($file_ids = $form_state->getValue($form_key)) {

        // Load the file entity.
        $file = $this->entityTypeManager->getStorage('file')->load(reset($file_ids));

        // Get file contents and convert the json to php arrays.
        // Load CSV file into array so each row has correct keys.
        $fp = fopen($file->getFileUri(), 'r');
        $key = fgetcsv($fp);

        // Add each row with the correct keys.
        $file_data = [];
        while ($row = fgetcsv($fp)) {
          $file_data[] = array_combine($key, $row);
        }
        fclose($fp);
        $data[$form_key] = $file_data;
      }
    }
    return $data;
  }

}
