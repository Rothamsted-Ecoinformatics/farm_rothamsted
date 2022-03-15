<?php

namespace Drupal\farm_rothamsted\Form;

use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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

    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('Please browse for your geoJSON file to be uploaded'),
    ];

    // Add file upload fields.
    $form['treatment_factors'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Treatment Factors'),
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
      '#upload_location' => 'public://',
      '#required' => TRUE,
    ];
    $form['treatment_factor_levels'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Treatment Factor Levels'),
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
      '#upload_location' => 'public://',
      '#required' => TRUE,
    ];
    $form['plot_assignments'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Plot Assignments'),
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
      '#upload_location' => 'public://',
      '#required' => TRUE,
    ];
    $form['plot_geometries'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Plot Geometries'),
      '#upload_validators' => [
        'file_validate_extensions' => ['geojson'],
      ],
      '#upload_location' => 'public://',
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
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Parse uploaded files.
    $file_data = $this->loadFiles($form_state);
    $treatment_factors = $file_data['treatment_factors'];
    $factor_levels = $file_data['treatment_factor_levels'];
    $plot_assignments = $file_data['plot_assignments'];
    $plot_assignment_ids = array_column($plot_assignments, 'plot_id');

    // Create and save new plan based on crs name.
    $plan = Plan::create([
      'type' => 'rothamsted_experiment',
      // @todo Plan name.
      'name' => 'Test plan',
      'status' => 'active',
      // @todo Include factor levels.
      'field_factors' => Json::encode($treatment_factors),
    ]);
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
      'status' => 'active',
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
        'name' => $plot_id,
        'status' => 'active',
        'intrinsic_geometry' => $wkt,
        'is_fixed' => TRUE,
        'is_location' => TRUE,
        'parent' => $experiment_land,
        'field_factors' => [],
      ];

      // Assign plot field values.
      $normal_fields = [
        'plot_id' => 'field_plot_id',
        'block' => 'field_block_id',
        'row' => 'field_row',
        'column' => 'field_col',
      ];
      foreach ($plot_attributes as $column_name => $column_value) {

        // Map the normal fields to the plot asset field.
        if (isset($normal_fields[$column_name])) {
          $plot_field_name = $normal_fields[$column_name];
          $plot_data[$plot_field_name] = $column_value;
        }
        // Else the column is a factor key/value pair.
        else {
          $plot_data['field_factors'][] = ['key' => $column_name, 'value' => $column_value];
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

      // Add asset to plan.
      $plan->get('asset')->appendItem($asset);
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
