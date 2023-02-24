<?php

namespace Drupal\farm_rothamsted_experiment\Form;

use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\farm_rothamsted\Traits\QuickFileTrait;
use Drupal\plan\Entity\PlanInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Serialization\Json;
use Drupal\asset\Entity\Asset;
use Drupal\plan\Entity\Plan;
use Drupal\geofield\GeoPHP\GeoPHPInterface;

/**
 * Rothamsted experiment upload form.
 *
 * Form with file upload to generate an experiment with plots based upon
 * the geoJson file uploaded.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class UploadExperimentForm extends FormBase {

  use QuickFileTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The selection plugin manager.
   *
   * @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface
   */
  protected $selectionPluginManager;

  /**
   * The GeoPHP service.
   *
   * @var \Drupal\geofield\GeoPHP\GeoPHPInterface
   */
  protected $geoPHP;

  /**
   * Constructs new experiment form.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $selection_plugin_manager
   *   The selection plugin manager.
   * @param \Drupal\geofield\GeoPHP\GeoPHPInterface $geo_PHP
   *   The GeoPHP service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, SelectionPluginManagerInterface $selection_plugin_manager, GeoPHPInterface $geo_PHP) {
    $this->entityTypeManager = $entity_type_manager;
    $this->selectionPluginManager = $selection_plugin_manager;
    $this->geoPHP = $geo_PHP;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
          $container->get('entity_type.manager'),
          $container->get('plugin.manager.entity_reference_selection'),
          $container->get('geofield.geophp'),
      );
  }

  /**
   * Getter method for Form ID.
   *
   * @return string
   *   The unique ID of the form defined by this class.
   */
  public function getFormId() {
    return 'upload_experiment_form';
  }

  /**
   * Returns a page title.
   */
  public function getTitle(PlanInterface $plan = NULL) {
    return $this->t('Upload experiment data for %label', ['%label' => $plan->label()]);
  }

  /**
   * Build the upload form.
   *
   * @param array $form
   *   Default form array structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object containing current form state.
   * @param \Drupal\plan\Entity\PlanInterface|null $plan
   *   The plan.
   *
   * @return array
   *   The render array defining the elements of the form.
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

    // Column and plots.
    $has_plots = !$plan->get('plot')->isEmpty();

    // Code and boundary needed for plots.
    $has_code = !$plan->get('experiment_code')->isEmpty();
    $has_boundary = FALSE;
    /** @var \Drupal\asset\Entity\AssetInterface[] $plan_assets */
    $plan_assets = $plan->get('asset')->referencedEntities();
    foreach ($plan_assets as $plan_asset) {
      if ($plan_asset->bundle() == 'land' && $plan_asset->get('land_type')->value == 'experiment_boundary') {
        $has_boundary = TRUE;
        break;
      }
    }

    // Brief instructions.
    $form['file_instructions'] = [
      '#markup' => $this->t('Upload experiment files in the order listed below. Each file will be checked to ensure it has no missing or inconsistent data.'),
    ];

    // Column group.
    $form['column_data'] = [
      '#type' => 'details',
      '#title' => $this->t('Experiment columns'),
      '#open' => TRUE,
    ];

    // Add file upload fields.
    $plan_file_location = $this->getFileUploadLocation('plan', 'rothamsted_experiment', 'file');
    $form['column_data']['column_descriptors'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Column descriptors'),
      '#description' => $this->t('CSV file containing the column descriptor definitions.'),
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
      '#upload_location' => $plan_file_location,
      '#limit_validation_errors' => [],
    ];
    $form['column_data']['column_levels'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Column Levels'),
      '#description' => $this->t('CSV file containing the column level definitions for each column descriptor.'),
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
      '#upload_location' => $plan_file_location,
      '#limit_validation_errors' => [],
    ];

    // Plot data group.
    $form['plot_data'] = [
      '#type' => 'details',
      '#title' => $this->t('Plots'),
      '#open' => TRUE,
    ];

    // Include message if no plots.
    if (!$has_plots) {
      $form['plot_data']['no_plots'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('No plots are associated with this experiment. Upload plot attributes and geometries to create plots.'),
      ];
    }

    // Include message if no code has been added.
    if (!$has_code) {
      $form['plot_data']['no_code'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Plots cannot be created without an experiment code.'),
      ];
    }

    // Include message if no boundary has been added.
    if (!$has_boundary) {
      $form['plot_data']['no_boundary'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Plots cannot be created without an experiment boundary.'),
      ];
    }

    $form['plot_data']['plot_attributes'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Plot attributes'),
      '#description' => $this->t('CSV file containing each plot number, id, type and column assignments.'),
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
      '#upload_location' => $plan_file_location,
      '#limit_validation_errors' => [],
      '#disabled' => !$has_boundary || !$has_code,
    ];

    $form['plot_data']['plot_geojson'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Plot geometries'),
      '#description' => $this->t('GeoJSON file containing each plot number and geometry.'),
      '#upload_validators' => [
        'file_validate_extensions' => ['geojson'],
      ],
      '#upload_location' => $plan_file_location,
      '#limit_validation_errors' => [],
      '#disabled' => !$has_boundary || !$has_code,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
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
      'plot_geojson',
    ];

    // Load all the file data for convenience.
    $file_data = $this->loadFiles($form_state);

    // Special case when a file is uploaded.
    $trigger = $form_state->getTriggeringElement();
    if (!empty($trigger['#array_parents']) && in_array($trigger['#array_parents'][1], $file_names)) {

      // Do not validate when removing a file.
      if ($trigger['#array_parents'][2] === 'remove_button') {
        return;
      }

      // Ensure necessary files are uploaded.
      $uploaded_file = $trigger['#array_parents'][1];
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
    $column_ids = array_column($file_data['column_levels'], 'column_id');

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
            $form_state->setError($form['plot_data']['plot_attributes'], $error_msg);
            $this->messenger()->addError($error_msg);
            continue;
          }

          // Check for numeric value type.
          if ($required_columns[$column_name] == 'numeric' && !is_numeric($column_value)) {
            $error_msg = "Invalid value for plot in row $row: $column_name is not numeric: $column_value";
            $form_state->setError($form['plot_data']['plot_attributes'], $error_msg);
            $this->messenger()->addError($error_msg);
            continue;
          }

          // Check for valid plot_type.
          if ($column_name == 'plot_type' && !in_array($column_value, $valid_plot_types)) {
            $error_msg = "Invalid plot type in row $row: $column_value";
            $form_state->setError($form['plot_data']['plot_attributes'], $error_msg);
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
          $form_state->setError($form['plot_data']['plot_attributes'], $error_msg);
          $this->messenger()->addError($error_msg);
          continue;
        }

        // Ensure each column_value is allowed for the column_name.
        // There should be a treatment factor level that has a matching
        // column_id and level_id.
        $matching_factor_levels = array_filter($file_data['column_levels'], function ($factor_level) use ($column_name, $column_value) {
          return $factor_level['column_id'] == $column_name && $factor_level['level_id'] == $column_value;
        });
        if ($column_value != 'na' && empty($matching_factor_levels)) {
          $error_msg = "Plot in row $row has an invalid level_id for column_id '$column_name': $column_value";
          $form_state->setError($form['plot_data']['plot_attributes'], $error_msg);
          $this->messenger()->addError($error_msg);
        }
      }
    }

    // Ensure we found plot_number 1.
    if (!$has_plot_1) {
      $error_msg = 'Missing plot_number 1. Make sure the plot number starts at 1.';
      $form_state->setError($form['plot_data']['plot_attributes'], $error_msg);
      $this->messenger()->addError($error_msg);
    }
  }

  /**
   * Validation function for the plot geojson file.
   *
   * @param array $file_data
   *   Processed file data.
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateFilePlotGeojson(array $file_data, array &$form, FormStateInterface $form_state) {

    // Get data from files.
    $plot_features = $file_data['plot_geojson']['features'];

    // Keep track of plot 1.
    $has_plot_1 = FALSE;

    // Ensure all required values are provided.
    $required_columns = [
      'plot_number' => 'numeric',
    ];
    foreach ($plot_features as $row => $feature) {

      // Increment row so count starts at 1 in error messages.
      $row++;

      // Make sure properties exist.
      if (!isset($feature['properties'])) {
        $error_msg = "Plot feature in row $row is missing properties.";
        $form_state->setError($form['plot_data']['plot_geojson'], $error_msg);
        $this->messenger()->addError($error_msg);
      }

      // Ensure that each plot has required columns.
      foreach ($feature['properties'] as $column_name => $column_value) {

        // Check required columns.
        if (in_array($column_name, array_keys($required_columns))) {

          if (!isset($feature['properties'][$column_name]) || strlen($feature['properties'][$column_name]) === 0) {
            $error_msg = "Plot feature in row $row is missing a $column_name";
            $form_state->setError($form['plot_data']['plot_geojson'], $error_msg);
            $this->messenger()->addError($error_msg);
            continue;
          }

          // Check for numeric value type.
          if ($required_columns[$column_name] == 'numeric' && !is_numeric($column_value)) {
            $error_msg = "Invalid value for plot feature in row $row: $column_name is not numeric: $column_value";
            $form_state->setError($form['plot_data']['plot_geojson'], $error_msg);
            $this->messenger()->addError($error_msg);
            continue;
          }

          // Mark if we found plot number 1.
          if ($column_name == 'plot_number' && (int) $feature['properties'][$column_name] == 1) {
            $has_plot_1 = TRUE;
          }
        }
      }
    }

    // Ensure we found plot_number 1.
    if (!$has_plot_1) {
      $error_msg = 'Missing plot_number 1. Make sure the plot number starts at 1.';
      $form_state->setError($form['plot_data']['plot_geojson'], $error_msg);
      $this->messenger()->addError($error_msg);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Get the plan.
    $plan = Plan::load($form_state->getValue('plan_id'));

    // Parse uploaded files.
    $file_data = $this->loadFiles($form_state);
    $column_descriptors = $file_data['column_descriptors'];
    $column_levels = $file_data['column_levels'];
    $plot_attributes_mapping = $file_data['plot_attributes'] ?? [];

    $plot_attributes_mapping = array_combine(array_column($plot_attributes_mapping, 'plot_number'), $plot_attributes_mapping);
    $plot_geojson = $file_data['plot_geojson']['features'] ?? [];
    foreach ($plot_geojson as $geojson_feature) {
      if ($number = $geojson_feature['properties']['plot_number']) {
        $plot_attributes_mapping[$number]['geojson'] = $geojson_feature;
      }
    }

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

    // Save each uploaded file on the plan.
    $files = [
      'column_descriptors',
      'column_levels',
      'plot_attributes',
      'plot_geojson',
    ];
    foreach ($files as $form_key) {
      if ($file_ids = $form_state->getValue($form_key)) {
        $plan->get('file')->appendItem(reset($file_ids));
      }
    }
    $plan->save();

    // Feedback link to created plan.
    $planUrl = $plan->toUrl()->toString();
    $planLabel = $plan->label();
    $this->messenger()->addMessage($this->t('Saved plan: <a href=":url">%asset_label</a>', [':url' => $planUrl, '%asset_label' => $planLabel]));

    // Iterate each of the saved features from the plot geometries file.
    $experiment_code = $plan->get('experiment_code')->value;

    $plot_parent = FALSE;
    /** @var \Drupal\asset\Entity\AssetInterface[] $plan_assets */
    $plan_assets = $plan->get('asset')->referencedEntities();
    foreach ($plan_assets as $plan_asset) {
      if ($plan_asset->bundle() == 'land' && $plan_asset->get('land_type')->value == 'experiment_boundary') {
        $plot_parent = $plan_asset->id();
        break;
      }
    }
    foreach ($plot_attributes_mapping as $attributes) {

      // Build the plot name from the feature data.
      $plot_id = $attributes['plot_id'];
      $plot_name = "$experiment_code: $plot_id";

      // Extract the intrinsic geometry references.
      // Re-encode into json.
      $featureJson = Json::encode($attributes['geojson'] ?? []);
      $wkt = $this->geoPHP->load($featureJson, 'json')->out('wkt');

      // Build data for the plot asset.
      $plot_data = [
        'type' => 'plot',
        'name' => $plot_name,
        'status' => 'active',
        'intrinsic_geometry' => $wkt,
        'is_fixed' => TRUE,
        'is_location' => FALSE,
        'parent' => $plot_parent,
        'column_descriptors' => [],
      ];

      // Assign plot field values.
      $normal_fields = [
        'plot_number',
        'plot_id',
        'plot_type',
        'row',
        'column',
      ];
      foreach ($plot_attributes_mapping as $column_name => $column_value) {

        // Map the normal fields to the plot asset field.
        if (in_array($column_name, $normal_fields)) {
          $plot_data[$column_name] = $column_value;
        }
        // Else the column is a factor key/value pair.
        // Don't include if the value is na.
        elseif ($column_value != 'na') {
          $plot_data['column_descriptors'][] = ['key' => $column_name, 'value' => $column_value];
        }
      }

      // Create and save plot assets.
      $asset = Asset::create($plot_data);

      // Save the plot asset.
      $asset->save();

      // Add plot asset to plan.
      $plan->get('plot')->appendItem($asset);
    }

    $plan->save();

    // Feedback of the number of features found, assumes all saved successfully.
    $this->messenger()->addMessage($this->t('Created %feature_count plots.', ['%feature_count' => count($plot_attributes_mapping)]));
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
    $files = [
      'column_descriptors' => 'csv',
      'column_levels' => 'csv',
      'plot_attributes' => 'csv',
      'plot_geojson' => 'geojson',
    ];
    foreach ($files as $form_key => $file_type) {

      // Get id of the submitted file.
      if ($file_ids = $form_state->getValue($form_key)) {

        // Load the file entity.
        $file = $this->entityTypeManager->getStorage('file')->load(reset($file_ids));

        // Get file contents and convert the json to php arrays.
        switch ($file_type) {
          case 'csv':
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
            break;

          case 'geojson':
            $file_data = file_get_contents($file->getFileUri());
            $data[$form_key] = Json::decode($file_data);
            break;
        }
      }

    }

    return $data;
  }

}
