<?php

namespace Drupal\farm_rothamsted\Plugin\QuickForm;

use Drupal\Core\Form\FormStateInterface;

/**
 * Spraying quick form.
 *
 * @QuickForm(
 *   id = "farm_rothamsted_spraying_quick_form",
 *   label = @Translation("Spraying"),
 *   description = @Translation("Create spraying records."),
 *   helpText = @Translation("Use this form to record spraying records."),
 *   permissions = {
 *     "create input log",
 *   }
 * )
 */
class QuickSpraying extends QuickExperimentFormBase {

  /**
   * {@inheritdoc}
   */
  protected $logType = 'input';

  /**
   * {@inheritdoc}
   */
  protected $tractorField = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $machineryGroupNames = ['Pesticide Equipment'];

  /**
   * {@inheritdoc}
   */
  protected bool $productsTab = TRUE;

  /**
   * {@inheritdoc}
   */
  protected bool $productBatchNum = TRUE;

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
      '#title' => $this->t('Spraying'),
      '#group' => 'tabs',
      '#weight' => 0,
    ];

    // Rename the products applied tab to be Fertiliser.
    $tank = &$form['products'];
    $tank['#title'] = $this->t('Tank Mix');

    // Weather tab.
    $weather = [
      '#type' => 'details',
      '#title' => $this->t('Weather'),
      '#group' => 'tabs',
      '#weight' => 6,
    ];

    // Health & safety tab.
    $health_and_safety = [
      '#type' => 'details',
      '#title' => $this->t('Health &amp; Safety'),
      '#group' => 'tabs',
      '#weight' => 7,
    ];

    // Add weight to equipment settings.
    $setup['equipment_settings']['#weight'] = 10;

    // Spray nozzle options.
    $spray_nozzle_options = $this->getGroupMemberOptions(['Spray Nozzles'], ['equipment']);
    $setup['nozzle_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Nozzle Type'),
      '#description' => $this->t('The type of spray nozzle used, where relevant.'),
      '#options' => $spray_nozzle_options,
      '#multiple' => TRUE,
    ];

    // Pressure.
    $setup['pressure'] = $this->buildQuantityField([
      'title' => $this->t('Pressure'),
      'description' => $this->t('The water pressure used when applying the product, where relevant.'),
      'measure' => ['#value' => 'pressure'],
      'units' => ['#value' => 'bar'],
    ]);

    // Build justification options from the Spray Applications parent term.
    $justification_options = $this->getChildTermOptionsByName('log_category', 'Justification/Target (Spray Applications)');

    // Justification/Target as log categories.
    $spraying['categories'] = [
      '#type' => 'select',
      '#title' => $this->t('Justification/Target'),
      '#description' => $this->t('The reason the operation is necessary, and any target pest(s) where applicable.'),
      '#options' => $justification_options,
      '#multiple' => TRUE,
      '#required' => TRUE,
    ];

    // Plant growth stage.
    $spraying['plant_growth_stage'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Plant growth stage'),
      '#description' => $this->t('The plant growth stage when the product was applied.'),
      '#required' => FALSE,
    ];

    // Seed labels.
    $spraying['seed_labels'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Seed labels'),
      '#description' => $this->t('Photograph(s) of the seed label taken prior to drilling or confirm the right seed batch and variety was used.'),
      '#upload_location' => $this->getFileUploadLocation('log', $this->logType, 'image'),
      '#upload_validators' => [
        'file_validate_extensions' => self::$validImageExtensions,
      ],
      '#multiple' => TRUE,
      '#extended' => TRUE,
      '#required' => TRUE,
    ];

    // Add the spraying tab and fields to the form.
    $form['spraying'] = $spraying;

    // Water volume.
    $water_volume_units_options = [
      'l' => 'l',
      'gal' => 'gal',
    ];
    $tank['water_volume'] = $this->buildQuantityField([
      'title' => $this->t('Water volume'),
      'description' => $this->t('The total amount of water required to cover the field area(s).'),
      'measure' => ['#value' => 'volume'],
      'units' => ['#options' => $water_volume_units_options],
      'required' => TRUE,
    ]);

    // Tank mix ID.
    $tank['tank_mix_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tank mix ID'),
      '#description' => $this->t('The record number for this tank mix. This is essential information if the same tank mix is applied over multiple crops or experiments.'),
      '#required' => FALSE,
    ];

    // COSSH Hazard Assessments.
    $health_and_safety['cossh_hazard'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('COSSH Hazard Assessments'),
      '#description' => $this->t('The COSHH assessments which need to be considered.'),
      '#options' => farm_rothamsted_cossh_hazard_options(),
      '#required' => TRUE,
    ];

    // PPE.
    $health_and_safety['ppe'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('PPE'),
      '#description' => $this->t('The protective clothing and equipment required for a specific job. Select all that apply to confirm they have been used.'),
      '#options' => farm_rothamsted_ppe_options(),
      '#required' => TRUE,
    ];

    // Knapsack Operator checklist - checkboxes - required.
    $health_and_safety['knapsack_operator_checklist'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Knapsack operator checklist'),
      '#description' => $this->t('An additional set of Health and Safety checks specifically for knapsack spraying which need to be marked off by the operator, as per Red Tractor Guidelines.'),
      '#options' => ['completed' => 'Completed'],
      '#required' => FALSE,
    ];

    // Add the health and safety tab and fields to the form.
    $form['health_and_safety'] = $health_and_safety;

    // Weather wrapper.
    $weather['weather_info'] = $this->buildInlineWrapper();

    // Weather types.
    $weather_types = [
      $this->t('Cloudy'),
      $this->t('Partially cloudy'),
      $this->t('Clear'),
      $this->t('Dry'),
      $this->t('Light rain'),
      $this->t('Heavy rain'),
      $this->t('Snow'),
      $this->t('Ice'),
      $this->t('Frost'),
      $this->t('Thunderstorms'),
    ];
    $weather_types_options = array_combine($weather_types, $weather_types);

    // Weather.
    $weather['weather_info']['weather'] = [
      '#type' => 'select',
      '#title' => $this->t('Weather'),
      '#description' => $this->t('The dominant weather conditions during spraying.'),
      '#options' => $weather_types_options,
      '#multiple' => TRUE,
      '#required' => TRUE,
    ];

    // Temperature (Degrees C).
    $weather['weather_info']['temperature'] = $this->buildQuantityField([
      'title' => $this->t('Temperature (C)'),
      'description' => $this->t('The average temperature during spraying.'),
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
      'title' => $this->t('Wind speed'),
      'description' => $this->t('The maximum wind speed during spraying.'),
      'measure' => ['#value' => 'ratio'],
      'units' => ['#options' => $wind_speed_units_options],
      'required' => TRUE,
    ];
    $weather['weather_info']['wind_speed'] = $this->buildQuantityField($wind_speed);

    // Wind direction.
    $wind_directions = [
      $this->t('North'),
      $this->t('South'),
      $this->t('East'),
      $this->t('West'),
      $this->t('North East'),
      $this->t('North West'),
      $this->t('South East'),
      $this->t('South West'),
    ];
    $wind_direction_options = array_combine($wind_directions, $wind_directions);
    $weather['weather_info']['wind_direction'] = [
      '#type' => 'select',
      '#title' => $this->t('Wind direction'),
      '#description' => $this->t('The dominant wind direction during spraying.'),
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
      'title' => $this->t('Area sprayed'),
      'description' => $this->t('The total area being sprayed.'),
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
      'title' => $this->t('Speed driven'),
      'description' => $this->t('The travelling speed when spraying, where relevant.'),
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
      'title' => $this->t('Tank volume remaining'),
      'description' => $this->t('If the full tank used enter zero. If not, estimate or calculate the remaining.'),
      'measure' => ['#value' => 'volume'],
      'units' => ['#options' => $tank_volume_ramaining_units_options],
    ];
    $operation['wrapper_2']['tank_volume_remaining'] = $this->buildQuantityField($tank_volume_remaining);

    // Equipment triple Rinsed.
    $operation['wrapper_2']['rinsed'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Equipment tripple rinsed'),
      '#description' => $this->t('Select if the equipment was triple rinsed after the job was completed.'),
      '#required' => TRUE,
    ];

    // Equipment clear washed.
    $operation['wrapper_2']['clear_washed'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Equipment clear washed'),
      '#description' => $this->t('Select if the equipment was clear washed after the job was completed.'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * Form ajax function for product quick form products.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The products render array.
   */
  public function productsCallback(array &$form, FormStateInterface $form_state) {
    return $form['spraying']['sprayed_products']['products'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getQuantities(array $field_keys, FormStateInterface $form_state): array {

    // @todo Include product quantities with correct label.
    $product_count = $form_state->getValue('product_count');
    for ($i = 0; $i < $product_count; $i++) {
      $field_keys[] = ['products', $i, 'product_rate'];
    }

    array_push(
      $field_keys,
      'pressure',
      'area_sprayed',
      'water_volume',
      'tank_volume_remaining',
      'wind_speed',
      'temperature',
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
  public function prepareLog(array $form, FormStateInterface $form_state): array {
    $log = parent::prepareLog($form, $form_state);

    // COSSH Hazard Assessments.
    $log['cossh_hazard'] = array_values(array_filter($form_state->getValue('cossh_hazard')));

    // PPE.
    $log['ppe'] = array_values(array_filter($form_state->getValue('ppe')));

    return $log;
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
          'key' => 'nozzle_type',
          'label' => $this->t('Nozzle type'),
        ],
        [
          'key' => 'tank_mix_id',
          'label' => $this->t('Tank Mix ID'),
        ],
      ]
    );
    return parent::prepareNotes($note_fields, $form_state);
  }

}
