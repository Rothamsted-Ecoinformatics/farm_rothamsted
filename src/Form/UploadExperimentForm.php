<?php

namespace Drupal\farm_rothamsted\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Serialization\Json;
use Drupal\asset\Entity\Asset;
use Drupal\plan\Entity\Plan;

/**
 * Rothamsted experiment upload form 
 *
 * Form with file upload to generate an experiment with plots based upon
 * the geoJson file uploaded
 *
 * @see \Drupal\Core\Form\FormBase
 */
class UploadExperimentForm extends FormBase {

  /**
   * The entity type manager service.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs new experiment form.
   *
   * @param Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
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
      '#default_value' => $this->configuration['json_file_upload'],
      '#title' => 'choose a File',
      '#description' => $this->t('File, #type = file'),
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

    // get id of the submitted file
    $fileIds = $form_state->getValue('json_file_upload', []);
    if (empty($fileIds)) {
      return $form;
    }

    // get reference to file
    $file = $this->entityTypeManager->getStorage('file')->load(reset($fileIds));

    // get file contents and convert the json to php arrays
    $data = file_get_contents($file->getFileUri());
    $json = Json::decode($data);

    // create and save new plan based on crs name
    $plan = Plan::create(
          [
            'type' => 'rothamsted_experiment',
            'name' => $json['crs']['properties']['name'],
            'status' => 'active',
          ]
      );
    $plan->save();

    // feedback link to created plan
    $planUrl = $plan->toUrl()->toString();
    $planLabel = $plan->label();
    $this->messenger()->addMessage($this->t('Added plan: <a href=":url">%asset_label</a>', [':url' => $planUrl, '%asset_label' => $planLabel]));

    // iterate each of the saved features from the file
    foreach ($json['features'] as $feature) {
      // re-encode the data into json
      $featureJson = Json::encode($feature);

      // extract the intrinsic geometry references
      $wkt = \geoPHP::load($featureJson, 'json')->out('wkt');

      // extract the plot name from the feature data
      $plotName = sprintf('ID: %03d Serial: %s', $feature['properties']['plot_id'], $feature['properties']['Serial']);

      // create and save plot assets
      $asset = Asset::create(
            [
              'type' => 'plot',
              'name' => $plotName,
              'status' => 'active',
              'intrinsic_geometry' => $wkt,
              'is_fixed' => TRUE,
              'is_location' => TRUE,
            ]
        );
      $asset->save();
    }

    // feedback of the number of features found - assumes all saved successfully
    $this->messenger()->addMessage($this->t('Added %feature_count features', ['%feature_count' => count($json['features'])]));
  }

}
