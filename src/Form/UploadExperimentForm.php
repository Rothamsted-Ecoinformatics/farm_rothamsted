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
 * Implements the SimpleForm form controller.
 *
 * This example demonstrates a simple form with a single text input element. We
 * extend FormBase which is the simplest form base class used in Drupal.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class UploadExperimentForm extends FormBase
{

    /**
     * The entity type manager service.
     *
     * @var Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * Constructs new barnaby form
     *
     * @param Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
     *   The entity type manager service.
     */
    public function __construct(EntityTypeManagerInterface $entity_type_manager)
    {
        $this->entityTypeManager = $entity_type_manager;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('entity_type.manager'),
        );
    }

    /**
     * Build the simple form.
     *
     * A build form method constructs an array that defines how markup and
     * other form elements are included in an HTML form.
     *
     * @param array                                $form
     *   Default form array structure.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   Object containing current form state.
     *
     * @return array
     *   The render array defining the elements of the form.
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {

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
        'file_validate_extensions' => ['geojson']
        ],
        '#upload_location' => 'public://'
        ];

        // Group submit handlers in an actions element with a key of "actions" so
        // that it gets styled correctly, and so that other modules may add actions
        // to the form. This is not required, but is convention.
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
     * The form ID is used in implementations of hook_form_alter() to allow other
     * modules to alter the render array built by this form controller. It must be
     * unique site wide. It normally starts with the providing module's name.
     *
     * @return string
     *   The unique ID of the form defined by this class.
     */
    public function getFormId()
    {
        return 'upload_experiment_form';
    }

    protected static function getIntrinsicGeomatryFromJson($json)
    {
        return \geoPHP::load($json, 'json')->out('wkt');
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {

        $fileIds = $form_state->getValue('json_file_upload', []);
        if (empty($fileIds)) {
            return $form;
        }

        $file = $this->entityTypeManager->getStorage('file')->load(reset($fileIds));

        $data = file_get_contents($file->getFileUri());
        $json = Json::decode($data);

        $plan = Plan::create(
            [
            'type' => 'rothamsted_experiment',
            'name' => $json['crs']['properties']['name'],
            'status' => 'active',
            ]
        );
        $plan->save();
    
        foreach ($json['features'] as $feature) {
            $featureJson = Json::encode($feature);

            $wkt = self::getIntrinsicGeomatryFromJson($featureJson);

            $plotName = sprintf('ID: %03d Serial: %s', $feature['properties']['plot_id'], $feature['properties']['Serial']);

            $asset = Asset::create(
                [
                'type' => 'plot',
                'name' => $plotName,
                'status' => 'active',
                'intrinsic_geometry' => $wkt,
                'is_fixed' => true,
                'is_location' => true,
                ]
            );
            $asset->save();
        }

        $this->messenger()->addMessage($this->t('Added %feature_count features', ['%feature_count' => count($json['features'])])); 

    }

}
