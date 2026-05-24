<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_quick\Plugin\QuickForm;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\farm_quick\Attribute\QuickForm;

/**
 * Spraying quick form.
 */
#[QuickForm(
  id: 'spraying',
  label: new TranslatableMarkup('Spraying'),
  description: new TranslatableMarkup('Create spraying records.'),
  helpText: new TranslatableMarkup('Use this form to record spraying records.'),
  permissions: [
    'create input log',
  ],
)]
class QuickSpraying extends QuickExperimentFormBase {

  /**
   * {@inheritdoc}
   */
  protected $logType = 'input';

  /**
   * {@inheritdoc}
   */
  protected $parentLogCategoryName = 'Spraying categories';

  /**
   * {@inheritdoc}
   */
  protected $tractorField = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $machineryEquipmentTypes = ['Pesticide Equipment'];

  /**
   * {@inheritdoc}
   */
  protected bool $productsTab = TRUE;

  /**
   * {@inheritdoc}
   */
  protected int $productsMinimum = 1;

  /**
   * {@inheritdoc}
   */
  protected $productBatchNum = self::PRODUCT_BATCH_NUM_REQUIRED;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Add to the setup tab.
    $setup = &$form['setup'];

    // Add to the operation tab.
    $operation = &$form['operation'];

    // Spraying tab.
    $spraying = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Spray Justification'),
      '#group' => 'tabs',
      '#weight' => 0,
    ];

    // Rename the products applied tab to be Fertiliser.
    $tank = &$form['products'];
    $tank['#title'] = new TranslatableMarkup('Tank Mix');

    // Weather tab.
    $weather = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Weather'),
      '#group' => 'tabs',
      '#weight' => 6,
    ];

    // Health & safety tab.
    $health_and_safety = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Health &amp; Safety'),
      '#group' => 'tabs',
      '#weight' => 7,
    ];

    // Add weight to equipment settings.
    $setup['equipment_settings']['#weight'] = 10;

    // Spray nozzle options.
    $spray_nozzle_options = $this->getEquipmentOptions(['Spray Nozzles']);
    $tags_identifier = 'nozzle_type';
    $setup['nozzle_type'] = [
      '#type' => 'select_tagify',
      '#title' => new TranslatableMarkup('Nozzle Type'),
      '#description' => new TranslatableMarkup('The type of spray nozzle used, where relevant.'),
      '#placeholder' => new TranslatableMarkup('Start typing to search available options...'),
      '#options' => $spray_nozzle_options,
      '#multiple' => TRUE,
      '#default_value' => [],
      '#mode' => '',
      '#identifier' => $tags_identifier,
      '#attributes' => [
        'class' => [$tags_identifier],
      ],
    ];

    // Pressure.
    $setup['pressure'] = $this->buildQuantityField([
      'title' => new TranslatableMarkup('Pressure'),
      'description' => new TranslatableMarkup('The water pressure used when applying the product, where relevant.'),
      'measure' => ['#value' => 'pressure'],
      'units' => ['#value' => 'bar'],
    ]);

    // Justification/Target.
    $spraying['justification_target'] = [
      '#type' => 'textfield',
      '#title' => new TranslatableMarkup('Justification/Target'),
      '#description' => new TranslatableMarkup('The reason the operation is necessary, and any target pest(s) where applicable.'),
      '#required' => TRUE,
    ];

    // Move recommendation fields to spraying tab.
    foreach (['recommendation_number', 'recommendation_files'] as $field_name) {
      $spraying[$field_name] = $form['setup'][$field_name];
      unset($form['setup'][$field_name]);
    }
    // Make recommendation_number required.
    $spraying['recommendation_number']['#required'] = TRUE;

    // Plant growth stage.
    $spraying['plant_growth_stage'] = [
      '#type' => 'textfield',
      '#title' => new TranslatableMarkup('Plant growth stage'),
      '#description' => new TranslatableMarkup('The plant growth stage when the product was applied.'),
      '#required' => FALSE,
    ];

    // Harvest interval.
    $harvest_intervals = [
      'days' => 'days',
      'weeks' => 'weeks',
    ];
    $spraying['harvest_interval'] = $this->buildQuantityField([
      'title' => new TranslatableMarkup('Harvest interval'),
      'description' => new TranslatableMarkup('For products with a specified interval between application and harvest, please make a note of the harvest interval here.'),
      'measure' => ['#value' => 'time'],
      'units' => ['#options' => $harvest_intervals],
    ]);

    // Add the spraying tab and fields to the form.
    $form['spraying'] = $spraying;

    // Water volume.
    $water_volume_units_options = [
      'l' => 'l',
      'gal' => 'gal',
    ];
    $tank['water_volume'] = $this->buildQuantityField([
      'title' => new TranslatableMarkup('Water volume'),
      'description' => new TranslatableMarkup('The total amount of water used.'),
      'measure' => ['#value' => 'volume'],
      'units' => ['#options' => $water_volume_units_options],
      'required' => TRUE,
    ]);

    // Application rate.
    $application_rate_units_options = [
      'l/ha' => 'l/ha',
    ];
    $tank['application_rate'] = $this->buildQuantityField([
      'title' => new TranslatableMarkup('Application rate'),
      'description' => new TranslatableMarkup('The combined application rate of the water plus any products used.'),
      'measure' => ['#value' => 'rate'],
      'units' => ['#options' => $application_rate_units_options],
      'required' => TRUE,
    ]);

    // Water rate.
    $water_rate_units_options = [
      'mm' => 'mm',
    ];
    $tank['water_rate'] = $this->buildQuantityField([
      'title' => new TranslatableMarkup('Water rate'),
      'description' => new TranslatableMarkup('Used for recording irrigation. The amount of water applied in mm as a rain gauge would record it. A water rate of 1mm = 10m3 water/ha. For older systems measuring in inches, 24mm is equivalent to an inch of rain (12mm for half an inch).'),
      'measure' => ['#value' => 'length'],
      'units' => ['#options' => $water_rate_units_options],
    ]);

    // Tank mix ID.
    $tank['tank_mix_id'] = [
      '#type' => 'textfield',
      '#title' => new TranslatableMarkup('Tank mix ID'),
      '#description' => new TranslatableMarkup('The record number for this tank mix. This is essential information if the same tank mix is applied over multiple crops or experiments.'),
      '#required' => FALSE,
    ];

    // COSSH Hazard Assessments.
    $health_and_safety['cossh_hazard'] = [
      '#type' => 'checkboxes',
      '#title' => new TranslatableMarkup('COSSH Hazard Assessments'),
      '#description' => new TranslatableMarkup('The COSHH assessments which need to be considered.'),
      '#options' => farm_rothamsted_cossh_hazard_options(),
      '#required' => TRUE,
    ];

    // PPE.
    $health_and_safety['ppe'] = [
      '#type' => 'checkboxes',
      '#title' => new TranslatableMarkup('PPE'),
      '#description' => new TranslatableMarkup('The protective clothing and equipment required for a specific job. Select all that apply to confirm they have been used.'),
      '#options' => farm_rothamsted_ppe_options(),
      '#required' => TRUE,
    ];

    // Knapsack Operator checklist - checkboxes - required.
    $health_and_safety['knapsack_operator_checklist'] = [
      '#type' => 'checkboxes',
      '#title' => new TranslatableMarkup('Knapsack operator checklist'),
      '#description' => new TranslatableMarkup('An additional set of Health and Safety checks specifically for knapsack spraying which need to be marked off by the operator, as per Red Tractor Guidelines.'),
      '#options' => ['completed' => 'Completed'],
      '#required' => FALSE,
    ];

    // Add the health and safety tab and fields to the form.
    $form['health_and_safety'] = $health_and_safety;

    // Weather wrapper.
    $weather['weather_info'] = $this->buildInlineWrapper();

    // Weather types.
    $weather_types = [
      new TranslatableMarkup('Cloudy'),
      new TranslatableMarkup('Partially cloudy'),
      new TranslatableMarkup('Clear'),
      new TranslatableMarkup('Dry'),
      new TranslatableMarkup('Light rain'),
      new TranslatableMarkup('Heavy rain'),
      new TranslatableMarkup('Snow'),
      new TranslatableMarkup('Ice'),
      new TranslatableMarkup('Frost'),
      new TranslatableMarkup('Thunderstorms'),
    ];
    $weather_types_options = array_combine($weather_types, $weather_types);

    // Weather.
    $weather['weather_info']['weather'] = [
      '#type' => 'checkboxes',
      '#title' => new TranslatableMarkup('Weather'),
      '#description' => new TranslatableMarkup('The dominant weather conditions during spraying.'),
      '#options' => $weather_types_options,
      '#multiple' => TRUE,
      '#required' => TRUE,
    ];

    // Temperature.
    $weather['weather_info']['temperature'] = $this->buildQuantityField([
      'title' => new TranslatableMarkup('Temperature'),
      'description' => new TranslatableMarkup('The average temperature during spraying.'),
      'measure' => ['#value' => 'temperature'],
      'units' => ['#value' => 'C'],
      'required' => TRUE,
    ]);

    // Wind speed.
    $wind_speed_units_options = [
      'kph' => 'kph',
      'mph' => 'mph',
    ];
    $wind_speed = [
      'title' => new TranslatableMarkup('Wind speed'),
      'description' => new TranslatableMarkup('The maximum wind speed during spraying.'),
      'measure' => ['#value' => 'ratio'],
      'units' => ['#options' => $wind_speed_units_options],
      'required' => TRUE,
    ];
    $weather['weather_info']['wind_speed'] = $this->buildQuantityField($wind_speed);

    // Wind direction.
    $wind_directions = [
      new TranslatableMarkup('North'),
      new TranslatableMarkup('South'),
      new TranslatableMarkup('East'),
      new TranslatableMarkup('West'),
      new TranslatableMarkup('North East'),
      new TranslatableMarkup('North West'),
      new TranslatableMarkup('South East'),
      new TranslatableMarkup('South West'),
    ];
    $wind_direction_options = array_combine($wind_directions, $wind_directions);
    $weather['weather_info']['wind_direction'] = [
      '#type' => 'select',
      '#title' => new TranslatableMarkup('Wind direction'),
      '#description' => new TranslatableMarkup('The dominant wind direction during spraying. Please select the general direction the wind is coming from.'),
      '#options' => $wind_direction_options,
      '#required' => TRUE,
    ];

    // Add the weather tab and fields to the form.
    $form['weather'] = $weather;

    $operation['wrapper_1'] = $this->buildInlineWrapper();
    $operation['wrapper_1']['#weight'] = 10;

    // Area sprayed.
    $area_sprayed_units_options = [
      'm2' => 'm2',
      'ha' => 'ha',
    ];
    $area_sprayed = [
      'title' => new TranslatableMarkup('Area sprayed'),
      'description' => new TranslatableMarkup('The total area being sprayed.'),
      'measure' => ['#value' => 'area'],
      'units' => ['#options' => $area_sprayed_units_options],
    ];
    $operation['wrapper_1']['area_sprayed'] = $this->buildQuantityField($area_sprayed);

    // Speed driven.
    $speed_driven_units_options = [
      'mph' => 'mph',
      'kmh' => 'km/h',
    ];
    $speed_driven = [
      'title' => new TranslatableMarkup('Speed driven'),
      'description' => new TranslatableMarkup('The travelling speed when spraying, where relevant.'),
      'measure' => ['#value' => 'ratio'],
      'units' => ['#options' => $speed_driven_units_options],
    ];
    $operation['wrapper_1']['speed_driven'] = $this->buildQuantityField($speed_driven);
    $operation['wrapper_1']['fuel_use'] = $operation['fuel_use'];
    unset($operation['fuel_use']);

    $operation['wrapper_2'] = $this->buildInlineWrapper();
    $operation['wrapper_2']['#weight'] = 10;

    // Tank volume remaining.
    $tank_volume_ramaining_units_options = [
      'l' => 'l',
      'gal' => 'gal',
    ];
    $tank_volume_remaining = [
      'title' => new TranslatableMarkup('Tank volume remaining'),
      'description' => new TranslatableMarkup('If the full tank used enter zero. If not, estimate or calculate the remaining.'),
      'measure' => ['#value' => 'volume'],
      'units' => ['#options' => $tank_volume_ramaining_units_options],
    ];
    $operation['wrapper_2']['tank_volume_remaining'] = $this->buildQuantityField($tank_volume_remaining);

    // Equipment triple Rinsed.
    $operation['wrapper_2']['equipment_rinsed'] = [
      '#type' => 'checkbox',
      '#title' => new TranslatableMarkup('Equipment triple rinsed'),
      '#description' => new TranslatableMarkup('Select if the equipment was triple rinsed after the job was completed.'),
      '#return_value' => 'Yes',
    ];

    // Equipment clear washed.
    $operation['wrapper_2']['equipment_washed'] = [
      '#type' => 'checkbox',
      '#title' => new TranslatableMarkup('Equipment clear washed'),
      '#description' => new TranslatableMarkup('Select if the equipment was clear washed after the job was completed.'),
      '#return_value' => 'Yes',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareLog(array $form, FormStateInterface $form_state): array {
    $log = parent::prepareLog($form, $form_state);

    // Add nozles to equipment list.
    if ($nozzles = $form_state->getValue('nozzle_type')) {
      if (!is_array($nozzles)) {
        $nozzles = [$nozzles];
      }
      array_push($log['equipment'], ...$nozzles);
    }

    // COSSH Hazard Assessments.
    $log['cossh_hazard'] = array_values(array_filter($form_state->getValue('cossh_hazard')));

    // PPE.
    $log['ppe'] = array_values(array_filter($form_state->getValue('ppe')));

    return $log;
  }

  /**
   * {@inheritdoc}
   */
  protected function getLogName(array $form, FormStateInterface $form_state): string {

    // Get all of the submitted material_types parent terms.
    $material_type_names = [];
    if ($product_count = $form_state->get('product_count')) {
      for ($i = 0; $i < $product_count; $i++) {
        $material_id = $form_state->getValue(['products', $i, 'product_wrapper', 'product_type']);
        if ($material_type = $this->entityTypeManager->getStorage('taxonomy_term')->load($material_id)) {
          $material_type_names[] = $material_type->label();
        }
      }
    }

    // Only include unique names.
    $material_type_names = array_unique($material_type_names);

    // Generate the log name.
    $name_parts = [
      'prefix' => 'Spraying: ',
      'products' => implode(', ', $material_type_names),
    ];
    $priority_keys = ['prefix', 'products'];
    return $this->prioritizedString($name_parts, $priority_keys);
  }

  /**
   * {@inheritdoc}
   */
  protected function getQuantities(array $field_keys, FormStateInterface $form_state): array {
    array_push(
      $field_keys,
      'harvest_interval',
      'pressure',
      'water_volume',
      'application_rate',
      'water_rate',
      'tank_volume_remaining',
      'wind_speed',
      'temperature',
      'area_sprayed',
      'speed_driven',
    );
    return parent::getQuantities($field_keys, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getImageIds(array $field_keys, FormStateInterface $form_state) {
    $field_keys[] = 'seed_labels';
    return parent::getImageIds($field_keys, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareNotes(array $note_fields, FormStateInterface $form_state): array {
    // Prepend additional note fields.
    array_unshift(
      $note_fields,
      ...[
        [
          'key' => 'justification_target',
          'label' => new TranslatableMarkup('Justification/Target'),
        ],
        [
          'key' => 'plant_growth_stage',
          'label' => new TranslatableMarkup('Plant Growth Stage'),
        ],
        [
          'key' => 'tank_mix_id',
          'label' => new TranslatableMarkup('Tank Mix ID'),
        ],
        [
          'key' => 'weather',
          'label' => new TranslatableMarkup('Weather'),
        ],
        [
          'key' => 'wind_direction',
          'label' => new TranslatableMarkup('Wind direction'),
        ],
        [
          'key' => 'knapsack_operator_checklist',
          'label' => new TranslatableMarkup('Knapsack operator checklist'),
        ],
        [
          'key' => 'equipment_rinsed',
          'label' => new TranslatableMarkup('Equipment triple-rinsed'),
        ],
        [
          'key' => 'equipment_washed',
          'label' => new TranslatableMarkup('Equipment clear washed'),
        ],
      ]
    );
    return parent::prepareNotes($note_fields, $form_state);
  }

}
