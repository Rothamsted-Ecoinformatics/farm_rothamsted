<?php

namespace Drupal\farm_rothamsted_experiment\Form;

use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\farm_rothamsted\Traits\QuickFileTrait;
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
   * Build the upload form.
   *
   * @param array $form
   *   Default form array structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object containing current form state.
   *
   * @return array
   *   The render array defining the elements of the form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Plan name.
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Experiment name'),
      '#required' => TRUE,
    ];

    // Experiment code.
    $form['experiment_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Experiment Code'),
      '#required' => TRUE,
    ];

    // Brief instructions.
    $form['file_instructions'] = [
      '#markup' => $this->t('Upload experiment files in the order listed below. Each file will be checked to ensure it has no missing or inconsistent data.'),
    ];

    // Add file upload fields.
    $plan_file_location = $this->getFileUploadLocation('plan', 'rothamsted_experiment', 'file');
    $form['treatment_factors'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Treatment Factors'),
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
      '#upload_location' => $plan_file_location,
      '#required' => TRUE,
    ];
    $form['treatment_factor_levels'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Treatment Factor Levels'),
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
      '#upload_location' => $plan_file_location,
      '#required' => TRUE,
    ];
    $form['plot_assignments'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Plot Assignments'),
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
      '#upload_location' => $plan_file_location,
      '#required' => TRUE,
    ];
    $form['plot_geometries'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Plot Geometries'),
      '#upload_validators' => [
        'file_validate_extensions' => ['geojson'],
      ],
      '#upload_location' => $plan_file_location,
      '#required' => TRUE,
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

    // Bail if not triggered by a file upload.
    $file_names = [
      'treatment_factors',
      'treatment_factor_levels',
      'plot_assignments',
      'plot_geometries',
    ];
    $trigger = $form_state->getTriggeringElement();
    if (empty($trigger['#array_parents']) || !in_array($trigger['#array_parents'][0], $file_names)) {
      return;
    }

    // Do not validate when removing a file.
    if ($trigger['#array_parents'][1] === 'remove_button') {
      return;
    }

    // Load all the file data for convenience.
    $file_data = $this->loadFiles($form_state);

    // Defer to the file validation function.
    $file_name = $trigger['#array_parents'][0];
    $function_name = 'validateFile' . str_replace('_', '', ucwords($file_name, '_'));
    $this->{$function_name}($file_data, $form, $form_state);
  }

  /**
   * Validation function for the treatment factors file.
   *
   * @param array $file_data
   *   Processed file data.
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateFileTreatmentFactors(array $file_data, array &$form, FormStateInterface $form_state) {

    // Ensure the file was parsed.
    if (empty($file_data['treatment_factors'])) {
      $form_state->setError($form['treatment_factors'], 'Failed to parse treatment factors.');
      return;
    }
    $factors = $file_data['treatment_factors'];

    // Ensure all required values are provided.
    $required_columns = ['treatment_factor_name', 'treatment_factor_id', 'treatment_factor_id'];
    foreach ($factors as $row => $factor) {
      $row++;
      foreach ($required_columns as $column_name) {
        if (!isset($factor[$column_name]) || strlen($factor[$column_name]) === 0) {
          $error_msg = "Treatment in row $row is missing a $column_name";
          $form_state->setError($form['treatment_factors'], $error_msg);
          $this->messenger()->addError($error_msg);
        }
      }
    }
  }

  /**
   * Validation function for the treatment factor levels file.
   *
   * @param array $file_data
   *   Processed file data.
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateFileTreatmentFactorLevels(array $file_data, array &$form, FormStateInterface $form_state) {

    // Ensure the file was parsed.
    if (empty($file_data['treatment_factor_levels'])) {
      $form_state->setError($form['treatment_factor_levels'], 'Failed to parse treatment factor levels.');
      return;
    }
    $levels = $file_data['treatment_factor_levels'];

    // Ensure treatment_factors was uploaded.
    if (empty($file_data['treatment_factors'])) {
      $form_state->setError($form['treatment_factor_levels'], 'Treatment factors must be uploaded first.');
      return;
    }
    $factor_ids = array_column($file_data['treatment_factors'], 'treatment_factor_id');

    // Ensure all required values are provided.
    $required_columns = ['treatment_factor_id', 'factor_level_name', 'factor_level_description'];
    foreach ($levels as $row => $level) {
      $row++;
      foreach ($required_columns as $column_name) {
        if (!isset($level[$column_name]) || strlen($level[$column_name]) === 0) {
          $error_msg = "Factor level in row $row is missing a $column_name";
          $form_state->setError($form['treatment_factor_levels'], $error_msg);
          $this->messenger()->addError($error_msg);
        }

        // Ensure each treatment_factor_id is defined in treatment_factors.
        if (!in_array($level['treatment_factor_id'], $factor_ids)) {
          $error_msg = "Factor level in row $row has an invalid treatment_factor_id: " . $level['treatment_factor_id'];
          $form_state->setError($form['treatment_factor_levels'], $error_msg);
          $this->messenger()->addError($error_msg);
        }

      }
    }
  }

  /**
   * Validation function for the plot assignments file.
   *
   * @param array $file_data
   *   Processed file data.
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateFilePlotAssignments(array $file_data, array &$form, FormStateInterface $form_state) {

    // Ensure the file was parsed.
    if (empty($file_data['plot_assignments'])) {
      $form_state->setError($form['plot_assignments'], 'Failed to parse plot assignments.');
      return;
    }
    $plots = $file_data['plot_assignments'];

    // Ensure treatment_factor_levels was uploaded.
    if (empty($file_data['treatment_factor_levels'])) {
      $form_state->setError($form['plot_assignments'], 'Treatment factor levels must be uploaded first.');
      return;
    }
    $factor_ids = array_column($file_data['treatment_factor_levels'], 'treatment_factor_id');
    $factor_level_names = array_column($file_data['treatment_factor_levels'], 'factor_level_name');

    // Ensure all required values are provided.
    $required_columns = ['plot_id', 'row', 'column'];
    $normal_columns = [...$required_columns, 'block'];
    foreach ($plots as $row => $plot) {
      $row++;
      foreach ($required_columns as $column_name) {
        if (!isset($plot[$column_name]) || strlen($plot[$column_name]) === 0) {
          $error_msg = "Plot in row $row is missing a $column_name";
          $form_state->setError($form['plot_assignments'], $error_msg);
          $this->messenger()->addError($error_msg);
        }

        // Ensure each that each plot has valid factor_ids and level_names.
        foreach ($plot as $column_name => $column_value) {
          if (in_array($column_name, $normal_columns)) {
            continue;
          }

          // Ensure each column is a valid factor id.
          if (!in_array($column_name, $factor_ids)) {
            $error_msg = "Plot in row $row has an invalid treatment_factor_id: $column_name";
            $form_state->setError($form['plot_assignments'], $error_msg);
            $this->messenger()->addError($error_msg);
            continue;
          }

          // Ensure each column_value is allowed for the factor_id.
          // $index should be an integer, and the column_name should exist
          // at that index in $factor_ids.
          $index = array_search($column_value, $factor_level_names);
          if ($index === FALSE) {
            $error_msg = "Plot in row $row has an invalid factor_level_name: $column_value";
            $form_state->setError($form['plot_assignments'], $error_msg);
            $this->messenger()->addError($error_msg);
            continue;
          }
          if ($factor_ids[$index] != $column_name) {
            $error_msg = "Plot in row $row has an factor_level_name ($column_value) from the wrong treatment_factor_id ($column_name)";
            $form_state->setError($form['plot_assignments'], $error_msg);
            $this->messenger()->addError($error_msg);
          }
        }
      }
    }
  }

  /**
   * Validation function for the plot geometries file.
   *
   * @param array $file_data
   *   Processed file data.
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateFilePlotGeometries(array $file_data, array &$form, FormStateInterface $form_state) {

    // Ensure the file was parsed.
    if (empty($file_data['plot_geometries']['features'])) {
      $form_state->setError($form['plot_geometries'], 'Failed to parse plot geometries.');
      return;
    }
    $plot_features = $file_data['plot_geometries']['features'];
    $feature_count = count($plot_features);

    // Ensure plot_assignments was uploaded.
    if (empty($file_data['plot_assignments'])) {
      $form_state->setError($form['plot_geometries'], 'Plot assignments must be uploaded first.');
      return;
    }
    $plot_ids = array_column($file_data['plot_assignments'], 'plot_id');
    $id_count = count($plot_ids);

    // Ensure the same count of plots.
    if ($feature_count != $id_count) {
      $form_state->setError($form['plot_geometries'], "Inconsistent plot count. Plot assignments: $id_count. Plot geometries: $feature_count");
      return;
    }

    // Ensure all required values are provided.
    $required_columns = ['plot_id'];
    foreach ($plot_features as $row => $feature) {
      $row++;

      // Make sure properties exist.
      if (!isset($feature['properties'])) {
        $error_msg = "Plot feature in row $row is missing properties.";
        $form_state->setError($form['plot_geometries'], $error_msg);
        $this->messenger()->addError($error_msg);
      }

      // Check required values.
      foreach ($required_columns as $column_name) {
        if (!isset($feature['properties'][$column_name]) || strlen($feature['properties'][$column_name]) === 0) {
          $error_msg = "Plot feature in row $row is missing a $column_name";
          $form_state->setError($form['plot_geometries'], $error_msg);
          $this->messenger()->addError($error_msg);
          continue;
        }

        // Ensure the plot_id is cross-referenced.
        if (!in_array($feature['properties']['plot_id'], $plot_ids)) {
          $error_msg = "Plot feature in row $row has an invalid plot_id. Check the plot_assignments csv.";
          $form_state->setError($form['plot_geometries'], $error_msg);
          $this->messenger()->addError($error_msg);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Parse uploaded files.
    $file_data = $this->loadFiles($form_state);
    $treatment_factors = $file_data['treatment_factors'];
    $factor_levels = $file_data['treatment_factor_levels'];
    $plot_assignments = $file_data['plot_assignments'];
    $plot_assignment_ids = array_column($plot_assignments, 'plot_id');

    // Build the plan factors JSON for plan.treatment_factors.
    $plan_factors = [];

    // First add each treatment factor.
    $factor_field_mapping = [
      'treatment_factor_id' => 'id',
      'treatment_factor_name' => 'name',
      'treatment_factor_uri' => 'uri',
      'treatment_factor_description' => 'description',
    ];
    foreach ($treatment_factors as $treatment_factor) {

      // Map treatment factor values.
      $factor_data = ['factor_levels' => []];
      foreach ($factor_field_mapping as $long => $short) {
        $factor_data[$short] = $treatment_factor[$long];
      }

      // Add to plan_factors.
      $id = $factor_data['id'];
      $plan_factors[$id] = $factor_data;
    }

    // Add factor levels.
    $factor_level_field_mapping = [
      'factor_level_name' => 'id',
      'label' => 'name',
      'factor_level_description' => 'description',
      'quantity' => 'quantity',
      'units' => 'units',
    ];
    foreach ($factor_levels as $factor_level) {
      // Map treatment factor level values.
      $level_data = [];
      foreach ($factor_level_field_mapping as $long => $short) {
        $level_data[$short] = $factor_level[$long];
      }

      // Add to the plan factors for the treatment factor.
      $id = $factor_level['treatment_factor_id'];
      $plan_factors[$id]['factor_levels'][] = $level_data;
    }

    // Sort plan_factors array to match the order of factors on plots.
    $treatment_factor_order = array_keys($plot_assignments[0]);
    usort($plan_factors, function ($a, $b) use ($treatment_factor_order) {
      $a_index = array_search($a['id'], $treatment_factor_order);
      $b_index = array_search($b['id'], $treatment_factor_order);
      return $a_index > $b_index;
    });

    // Create and save new plan based on crs name.
    $experiment_code = $form_state->getValue('experiment_code');
    $plan = Plan::create([
      'type' => 'rothamsted_experiment',
      'name' => $form_state->getValue('name'),
      'status' => 'planning',
      'experiment_code' => $experiment_code,
      'treatment_factors' => Json::encode(array_values($plan_factors)),
    ]);

    // Save each uploaded file on the plan.
    $files = ['treatment_factors', 'treatment_factor_levels', 'plot_assignments', 'plot_geometries'];
    foreach ($files as $form_key) {
      if ($file_ids = $form_state->getValue($form_key)) {
        $plan->get('file')->appendItem(reset($file_ids));
      }
    }
    $plan->save();

    // Feedback link to created plan.
    $planUrl = $plan->toUrl()->toString();
    $planLabel = $plan->label();
    $this->messenger()->addMessage($this->t('Added plan: <a href=":url">%asset_label</a>', [':url' => $planUrl, '%asset_label' => $planLabel]));

    // Create and save land asset.
    $experiment_land = Asset::create([
      'type' => 'land',
      'land_type' => 'other',
      'name' => $this->t('@plan_name Experiment Surrounds', ['@plan_name' => $plan->label()]),
      'status' => 'planning',
      'is_fixed' => TRUE,
      'is_location' => TRUE,
    ]);
    $experiment_land->save();

    // Add land asset to the plan.
    $plan->get('asset')->appendItem($experiment_land);

    // Iterate each of the saved features from the plot geometries file.
    $features = $file_data['plot_geometries']['features'];
    foreach ($features as $feature) {

      // Extract the intrinsic geometry references.
      // Re-encode into json.
      $featureJson = Json::encode($feature);
      $wkt = $this->geoPHP->load($featureJson, 'json')->out('wkt');

      // Extract the plot name from the feature data.
      $plot_id = $feature['properties']['plot_id'];

      $plot_index = array_search($plot_id, $plot_assignment_ids);
      $plot_attributes = $plot_assignments[$plot_index];

      // Build data for the plot asset.
      $plot_data = [
        'type' => 'plot',
        'name' => "$experiment_code: $plot_id",
        'status' => 'active',
        'intrinsic_geometry' => $wkt,
        'is_fixed' => TRUE,
        'is_location' => TRUE,
        'parent' => $experiment_land,
        'treatment_factors' => [],
      ];

      // Assign plot field values.
      $normal_fields = ['plot_id', 'block', 'row', 'column'];
      foreach ($plot_attributes as $column_name => $column_value) {

        // Map the normal fields to the plot asset field.
        if (in_array($column_name, $normal_fields)) {
          $plot_data[$column_name] = $column_value;
        }
        // Else the column is a factor key/value pair.
        else {
          $plot_data['treatment_factors'][] = ['key' => $column_name, 'value' => $column_value];
        }
      }

      // Create and save plot assets.
      $asset = Asset::create($plot_data);

      // If specified, add the crop.
      // @todo We need the csv to include crop.
      if (isset($feature['properties']['crop'])) {

        // Use the taxonomy term selection handler to check existing terms.
        $options = [
          'target_type' => 'taxonomy_term',
          'target_bundles' => ['plant_type'],
          'handler' => 'default:taxonomy_term',
        ];
        $handler = $this->selectionPluginManager->getInstance($options);
        $crop_name = $feature['properties']['crop'];
        $existing_terms = $handler->getReferenceableEntities($crop_name, '=', 1);

        // Use the existing term.
        if (!empty($existing_terms['plant_type'])) {
          $plant_type = key($existing_terms['plant_type']);
        }
        // Else create a new term.
        else {
          /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $handler */
          $plant_type = $handler->createNewEntity('taxonomy_term', 'plant_type', $crop_name, $this->currentUser()->id());
          $plant_type->save();
        }
        $asset->set('plant_type', $plant_type);
      }

      // Save the plot asset.
      $asset->save();

      // Add plot asset to plan.
      $plan->get('plot')->appendItem($asset);
    }

    $plan->save();

    // Feedback of the number of features found, assumes all saved successfully.
    $this->messenger()->addMessage($this->t('Added %feature_count features', ['%feature_count' => count($features)]));
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
      'treatment_factors' => 'csv',
      'treatment_factor_levels' => 'csv',
      'plot_assignments' => 'csv',
      'plot_geometries' => 'geojson',
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
