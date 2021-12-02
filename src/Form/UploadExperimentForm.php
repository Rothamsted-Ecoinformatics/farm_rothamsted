<?php

namespace Drupal\farm_rothamsted\Form;

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
   * @param \Drupal\geofield\GeoPHP\GeoPHPInterface $geo_PHP
   *   The GeoPHP service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, GeoPHPInterface $geo_PHP) {
    $this->entityTypeManager = $entity_type_manager;
    $this->geoPHP = $geo_PHP;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
          $container->get('entity_type.manager'),
          $container->get('geofield.geophp'),
      );
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

    $form['json_file_upload'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('File'),
      '#upload_validators' => [
        'file_validate_extensions' => ['geojson'],
      ],
      '#upload_location' => 'public://',
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
   * Getter method for Form ID.
   *
   * @return string
   *   The unique ID of the form defined by this class.
   */
  public function getFormId() {
    return 'upload_experiment_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Get id of the submitted file.
    $fileIds = $form_state->getValue('json_file_upload', []);
    if (empty($fileIds)) {
      return $form;
    }

    // Get reference to file.
    $file = $this->entityTypeManager->getStorage('file')->load(reset($fileIds));

    // Get file contents and convert the json to php arrays.
    $data = file_get_contents($file->getFileUri());
    $json = Json::decode($data);

    // Extract factors json.
    $factors = $json['factors'];

    // Create and save new plan based on crs name.
    $plan = Plan::create(
          [
            'type' => 'rothamsted_experiment',
            'name' => $json['name'],
            'status' => 'active',
            'field_factors' => Json::encode($factors),
          ]
      );
    $plan->save();

    // Feedback link to created plan.
    $planUrl = $plan->toUrl()->toString();
    $planLabel = $plan->label();
    $this->messenger()->addMessage($this->t('Added plan: <a href=":url">%asset_label</a>', [':url' => $planUrl, '%asset_label' => $planLabel]));

    // Iterate each of the saved features from the file.
    foreach ($json['features'] as $feature) {
      // re-encode the data into json.
      $featureJson = Json::encode($feature);

      // Extract the intrinsic geometry references.
      $wkt = $this->geoPHP->load($featureJson, 'json')->out('wkt');

      // Extract the plot name from the feature data.
      $plotName = $feature['properties']['plot_label'];

      // Iterate factors and add to plot as key value field.
      $factors = [];
      foreach ($feature['properties']['factors'] as $fact) {
        $key = key($fact);
        $val = reset($fact);
        $factors[] = ['key' => $key, 'value' => $val];
      }

      // Create and save plot assets.
      $asset = Asset::create(
            [
              'type' => 'plot',
              'name' => $plotName,
              'status' => 'active',
              'intrinsic_geometry' => $wkt,
              'is_fixed' => TRUE,
              'is_location' => TRUE,
              'plot_id' => $feature['properties']['plot_id'],
              'block_id' => $feature['properties']['block'],
              'row' => $feature['properties']['row'],
              'col' => $feature['properties']['col'],
              'field_factors' => $factors,
            ]
        );
      $asset->save();

      // Add asset to plan.
      $plan->get('asset')->appendItem($asset);
    }

    $plan->save();

    // Feedback of the number of features found, assumes all saved successfully.
    $this->messenger()->addMessage($this->t('Added %feature_count features', ['%feature_count' => count($json['features'])]));
  }

}
