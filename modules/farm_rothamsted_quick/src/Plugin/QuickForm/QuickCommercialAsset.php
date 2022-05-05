<?php

namespace Drupal\farm_rothamsted_quick\Plugin\QuickForm;

use Drupal\asset\Entity\AssetInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\farm_quick\Plugin\QuickForm\QuickFormBase;
use Drupal\farm_quick\Traits\QuickAssetTrait;
use Drupal\farm_quick\Traits\QuickLogTrait;
use Drupal\farm_rothamsted\Traits\QuickFileTrait;
use Drupal\farm_rothamsted_quick\Traits\QuickTaxonomyOptionsTrait;
use Drupal\taxonomy\TermInterface;
use Psr\Container\ContainerInterface;

/**
 * Commercial asset quick form.
 *
 * @QuickForm(
 *   id = "commercial_asset",
 *   label = @Translation("Commercial Plant Assets"),
 *   description = @Translation("Create commercial plant assets."),
 *   helpText = @Translation("Use this form to create commercial plant assets."),
 *   permissions = {
 *     "create plant asset",
 *   }
 * )
 */
class QuickCommercialAsset extends QuickFormBase {

  use QuickAssetTrait;
  use QuickFileTrait;
  use QuickLogTrait;
  use QuickTaxonomyOptionsTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a QuickFormBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MessengerInterface $messenger, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $messenger);
    $this->messenger = $messenger;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('messenger'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Plant asset name.
    // Provide a checkbox to allow customizing this. Otherwise it will be
    // automatically generated on submission.
    $form['custom_name'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Customize plant asset name'),
      '#description' => $this->t('The name of the commercial crop asset. Defaults to: "[Location] [Crop] [Season]"'),
      '#default_value' => FALSE,
      '#ajax' => [
        'callback' => [$this, 'plantNameCallback'],
        'wrapper' => 'plant-name',
      ],
    ];
    $form['name_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'plant-name'],
    ];
    if ($form_state->getValue('custom_name', FALSE)) {
      $form['name_wrapper']['name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Plant asset name'),
        '#maxlength' => 255,
        '#default_value' => $this->generatePlantName($form_state),
        '#required' => TRUE,
      ];
    }

    // Field/location.
    $form['location'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Field/Location'),
      '#description' => $this->t('The field in which the asset is planted. If the area is not present in the list, it can be added as a new land asset.'),
      '#target_type' => 'asset',
      '#selection_handler' => 'views',
      '#selection_settings' => [
        'view' => [
          'view_name' => 'farm_location_reference',
          'display_name' => 'entity_reference',
          'arguments' => [],
        ],
        'match_operator' => 'CONTAINS',
      ],
      '#tags' => TRUE,
      '#required' => TRUE,
    ];

    // Seasons.
    $season_options = $this->getTermTreeOptions('season');
    $form['season'] = [
      '#type' => 'select',
      '#title' => $this->t('Season'),
      '#description' => $this->t('The season in which the assets is planted. E.g. Winter 2020 or Spring 2021. This can be expanded in the Seasons Taxonomy'),
      '#options' => $season_options,
      '#required' => TRUE,
    ];

    // Crop type.
    $form['crop'] = $this->buildInlineWrapper();
    $crop_type_options = $this->getTermTreeOptions('plant_type', 0, 1);
    $form['crop']['crop'] = [
      '#type' => 'select',
      '#title' => $this->t('Crop(s)'),
      '#description' => $this->t('The crop(s) being drilled. This can be expanded in the Plant Types taxonomy.'),
      '#options' => $crop_type_options,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [$this, 'cropVarietyCallback'],
        'event' => 'change',
        'wrapper' => 'crop-variety-wrapper',
      ],
    ];

    // Crop variety.
    $crop_variety_options = [];
    if ($crop_id = $form_state->getValue('crop')) {
      $crop_variety_options = $this->getTermTreeOptions('plant_type', $crop_id);
    }
    $form['crop']['plant_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Variety(s)'),
      '#description' => $this->t('The variety(s) being planted. To select more than one option on a desktop PC hold down the CTRL button on and select multiple.'),
      '#options' => $crop_variety_options,
      '#multiple' => TRUE,
      '#required' => TRUE,
      '#validated' => TRUE,
      '#prefix' => '<div id="crop-variety-wrapper">',
      '#suffix' => '</div>',
    ];

    // Associated files.
    $form['file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Associated files'),
      '#description' => $this->t('The option to upload one or more files relating to this plant asset.'),
      '#upload_location' => $this->getFileUploadLocation('asset', 'plant', 'file'),
      '#upload_validators' => [
        'file_validate_extensions' => self::$validFileExtensions,
      ],
      '#multiple' => TRUE,
      '#extended' => TRUE,
    ];

    // Asset notes.
    $form['notes'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Notes'),
      '#description' => $this->t('Any additional notes, not captured above.'),
      '#format' => 'default',
    ];

    return $form;
  }

  /**
   * Ajax callback for plant name field.
   */
  public function plantNameCallback(array $form, FormStateInterface $form_state) {
    return $form['name_wrapper'];
  }

  /**
   * Ajax callback for the crop variety field.
   */
  public function cropVarietyCallback(array $form, FormStateInterface $form_state) {
    return $form['crop']['plant_type'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // If a custom plant name was provided, use that. Otherwise generate one.
    $plant_name = $this->generatePlantName($form_state);
    if (!empty($form_state->getValue('custom_name', FALSE)) && $form_state->hasValue('name')) {
      $plant_name = $form_state->getValue('name');
    }

    // Start an array of asset data.
    $asset_data = [
      'type' => 'plant',
      'name' => $plant_name,
      'plant_type' => $form_state->getValue('plant_type'),
      'season' => $form_state->getValue('season'),
      'file' => $form_state->getValue('file', []),
      'notes' => $form_state->getValue('notes'),
    ];

    // If multiple files are uploaded than use the 'fids' key.
    $fids = $form_state->getValue('file');
    if (!empty($fids) && is_array($fids)) {
      $fids = $fids['fids'];
    }
    $asset_data['file'] = $fids;

    // Create the asset.
    $asset = $this->createAsset($asset_data);
    $asset->save();

    // Get the location name.
    $location_names = [];
    if ($location_ids = array_column($form_state->getValue('location', []), 'target_id')) {
      if ($locations = $this->entityTypeManager->getStorage('asset')->loadMultiple($location_ids)) {
        $location_names = array_map(function (AssetInterface $location) {
          return $location->label();
        }, $locations);
      }
    }

    // Assign the asset's location.
    $locations = $form_state->getValue('location');
    $log = $this->createLog([
      'type' => 'activity',
      'name' => $this->t('Move @asset to @location', ['@asset' => $asset->label(), '@location' => implode(', ', $location_names)]),
      'asset' => $asset,
      'location' => $locations,
      'is_movement' => TRUE,
      'status' => 'done',
    ]);
    $log->save();
  }

  /**
   * Generate plant asset name.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return string
   *   Returns a plant asset name string.
   */
  protected function generatePlantName(FormStateInterface $form_state) {

    // Get the season name.
    $season_name = '';
    if ($season = $form_state->getValue('season')) {
      $season_term = $this->entityTypeManager->getStorage('taxonomy_term')->load($season);
      $season_name = $season_term->label();
    }

    // Get the crop/variety names.
    /** @var \Drupal\taxonomy\TermInterface[] $crops */
    $crops = $form_state->getValue('plant_type', []);
    $crop_names = [];
    foreach ($crops as $crop) {
      if (is_numeric($crop)) {
        $crop = $this->entityTypeManager->getStorage('taxonomy_term')->load($crop);
      }
      if ($crop instanceof TermInterface) {
        $crop_names[] = $crop->label();
      }
    }

    // Get the location name.
    $location_names = [];
    if ($location_ids = array_column($form_state->getValue('location', []), 'target_id')) {
      if ($locations = $this->entityTypeManager->getStorage('asset')->loadMultiple($location_ids)) {
        $location_names = array_map(function (AssetInterface $location) {
          return $location->label();
        }, $locations);
      }
    }

    // Generate the plant name.
    $name_parts = [
      'location' => implode(' ', $location_names),
      'crops' => implode(', ', $crop_names),
      'season' => $season_name,
    ];
    $priority_keys = ['location', 'seasons', 'crops'];
    return $this->prioritizedString($name_parts, $priority_keys);
  }

  /**
   * Helper function to build an inline wrapper container.
   *
   * @return array
   *   An inline wrapper render array.
   */
  protected function buildInlineWrapper(): array {
    return [
      '#type' => 'container',
      '#attributes' => [
        'style' => ['display: flex; flex-wrap: wrap; column-gap: 2em;'],
      ],
    ];
  }

}
