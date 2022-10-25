<?php

namespace Drupal\farm_rothamsted_quick\Plugin\QuickForm;

use Drupal\asset\Entity\AssetInterface;
use Drupal\Component\Utility\NestedArray;
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

    // Field/location.
    $form['location'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Field/Location'),
      '#description' => $this->t('The field in which the asset is planted. If the area is not present in the list, it can be added as a new land asset.'),
      '#target_type' => 'asset',
      '#selection_handler' => 'views',
      '#selection_settings' => [
        'view' => [
          'view_name' => 'rothamsted_quick_location_reference',
          'display_name' => 'entity_reference',
          'arguments' => [],
        ],
        'match_operator' => 'CONTAINS',
      ],
      '#tags' => TRUE,
      '#required' => TRUE,
    ];

    // Drilling year.
    $drilling_options = $this->getChildTermOptionsByName('season', 'Drilling year');
    $form['drilling_year'] = [
      '#type' => 'select',
      '#title' => $this->t('Drilling year'),
      '#description' => $this->t('The season in which the assets is planted. This can be expanded by adding child terms to the "Drilling year" term in the Seasons Taxonomy'),
      '#options' => $drilling_options,
      '#required' => TRUE,
    ];

    // Harvest year.
    $harvest_options = $this->getChildTermOptionsByName('season', 'Harvest year');
    $form['harvest_year'] = [
      '#type' => 'select',
      '#title' => $this->t('Harvest year'),
      '#description' => $this->t('The year in which the asset will be harvested. This can be expanded by adding child terms to the "Harvest year" term in the Seasons Taxonomy.'),
      '#options' => $harvest_options,
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
    $crop_variety_options = NestedArray::getValue($form_state->getStorage(), ['plant_type']) ?? [];
    if ($crop_id = $form_state->getValue('crop')) {
      $crop_variety_options = $this->getTermTreeOptions('plant_type', $crop_id);
      NestedArray::setValue($form_state->getStorage(), ['plant_type'], $crop_variety_options);
    }
    $form['crop']['plant_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Variety(s)'),
      '#description' => $this->t('The variety(s) being planted. To select more than one option on a desktop PC hold down the CTRL button on and select multiple.'),
      '#options' => $crop_variety_options,
      '#multiple' => TRUE,
      '#prefix' => '<div id="crop-variety-wrapper">',
      '#suffix' => '</div>',
    ];

    // Plant asset name.
    // Provide a checkbox to allow customizing this. Otherwise it will be
    // automatically generated on submission.
    $form['custom_name'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Customize plant asset name'),
      '#description' => $this->t('The name of the commercial crop asset. Defaults to: "[Harvest year] [Location]: [Crop] ([Variety])"'),
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

    // For the plant_type use the crop unless the variety is specified.
    $plant_type = $form_state->getValue('crop');
    if ($variety = $form_state->getValue('plant_type')) {
      $plant_type = $variety;
    }

    // Start an array of asset data.
    $asset_data = [
      'type' => 'plant',
      'name' => $plant_name,
      'plant_type' => $plant_type,
      'season' => [$form_state->getValue('drilling_year'), $form_state->getValue('harvest_year')],
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

    // Get the harvest year name. This is a season taxonomy term.
    $season_name = '';
    if ($season = $form_state->getValue('harvest_year')) {
      $season_term = $this->entityTypeManager->getStorage('taxonomy_term')->load($season);
      $season_label = $season_term->label();

      // Remove "Harvest year:" prefix.
      $parts = explode(':', $season_label);
      $season_name = $season_label;
      if (count($parts) == 2) {
        $season_name = trim($parts[1]);
      }
    }

    // Get the crop/variety names.
    $crop_name = '';
    if ($crop_id = $form_state->getValue('crop')) {
      if ($crop = $this->entityTypeManager->getStorage('taxonomy_term')->load($crop_id)) {
        $crop_name = $crop->label();
      }
    }

    // Get the variety names.
    $variety_names = [];
    if ($variety_ids = $form_state->getValue('plant_type', [])) {
      if ($varieties = $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple($variety_ids)) {
        $variety_names = array_map(function (TermInterface $variety) {
          return $variety->label();
        }, $varieties);
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
      'season' => $season_name,
      'location' => implode(' ', $location_names) . ':',
      'crop' => $crop_name,
      // If no variety is selected don't include parenthesis.
      'variety' => empty($variety_names) ? '' : '(' . implode(', ', $variety_names) . ')',
    ];
    $priority_keys = ['season', 'location', 'crop', 'variety'];
    return $this->prioritizedString($name_parts, $priority_keys, 255, '...)');
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
