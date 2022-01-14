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
  protected $tractorField = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $machineryGroupNames = ['Pesticide Equipment'];

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $weight = 200;
    $form = parent::buildForm($form, $form_state);

    // ---------------- product area --------------------
    // @todo wrap with ajax - multiple products

    $form['product'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Product'),
      '#required' => TRUE,
      '#description' => $this->t('The product used. The list can be expanded or amended in the inputs taxonomy.'),
      '#weight' => ++$weight,
    ];

    $form['product_rate'] = [
      '#type' => 'number',
      '#title' => $this->t('Product rate'),
      '#required' => TRUE,
      '#description' => $this->t('The rate the product is applied per unit area. This is usually specified in the agronomists recommendations.'),
      '#weight' => ++$weight,
    ];

    // Product rate units.
    $product_rate_units = [
      '',
      'l/ha',
      'kg/ha',
      'ml/ha',
      'g/ha',
    ];
    $product_rate_unit_options = array_combine($product_rate_units, $product_rate_units);

    $form['product_rate_units'] = [
      '#type' => 'select',
      '#title' => $this->t('Product rate units'),
      '#required' => TRUE,
      '#options' => $product_rate_unit_options,
      '#weight' => ++$weight,
    ];
    // ------------end of product area --------------------

    // @todo Number of chemicals.

    // @todo AJAX for each chemical.

    // Build justification options from the Spray Applications parent term.
    $justification_options = $this->getChildTermOptions('log_category', 'Justification/Target (Spray Applications)');

    // Justification/Target as log categories.
    $form['categories'] = [
      '#type' => 'select',
      '#title' => $this->t('Justification/Target'),
      '#options' => $justification_options,
      '#multiple' => TRUE,
      '#required' => TRUE,
      '#description' => $this->t('The reason the operation is necessary, and any target pest(s) where applicable.'),
      '#weight' => ++$weight,
    ];

    // -------------------- spray days -----------------------
    // @todo wrap in ajax - button to add another day

    // Area sprayed.
    $form['area_sprayed'] = [
      '#type' => 'number',
      '#title' => $this->t('Area sprayed'),
      '#input_group' => TRUE,
      '#required' => FALSE,
      '#description' => $this->t('The total area being sprayed.'),
      '#weight' => ++$weight,
    ];

    // Area sprayed units options.
    $area_sprayed_units_options = [
      'm2' => 'm2',
      'ha' => 'ha',
    ];

    // Area sprayed units.
    $form['area_sprayed_units'] = [
      '#type' => 'radios',
      '#title' => $this->t('Area sprayed units'),
      '#options' => $area_sprayed_units_options,
      '#description' => $this->t('The area sprayed units.'),
      '#weight' => ++$weight,
    ];

    // RRES product number.
    $form['rres_product_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('RRES product number'),
      '#required' => TRUE,
      '#description' => $this->t('A unique identifier for each product (usually the suppliers batch number).'),
      '#weight' => ++$weight,
    ];

    // Product quantity.
    $form['product_quantity'] = [
      '#type' => 'number',
      '#title' => $this->t('Product quantity'),
      '#required' => TRUE,
      '#description' => $this->t('The total amount of product required to cover the field area(s)'),
      '#weight' => ++$weight,
    ];

    // Product quantity units options.
    $product_quantity_units_options = [
      'l' => 'l',
      'kg' => 'kg',
      'ml' => 'ml',
      'gal' => 'gal',
    ];

    // Product quantity units.
    $form['product_quantity_units'] = [
      '#type' => 'radios',
      '#title' => $this->t('Product quantity units'),
      '#options' => $product_quantity_units_options,
      '#required' => TRUE,
      '#description' => $this->t('The product quantity units.'),
      '#weight' => ++$weight,
    ];

    // Water volume.
    $form['water_volume'] = [
      '#type' => 'number',
      '#title' => $this->t('Water volume'),
      '#required' => TRUE,
      '#description' => $this->t('The total amount of water required to cover the field area(s).'),
      '#weight' => ++$weight,
    ];

    // Water volume units options.
    $water_volume_units_options = [
      'l' => 'l',
      'gal' => 'gal',
    ];

    // Water volume units.
    $form['product_quantity_units'] = [
      '#type' => 'radios',
      '#title' => $this->t('Water volume units'),
      '#options' => $water_volume_units_options,
      '#required' => TRUE,
      '#description' => $this->t('The water volume units.'),
      '#weight' => ++$weight,
    ];

    // Build hazard options.
    // @todo Determine way to define hazard options. See issue #64.
    $hazard_options = ['explosive' => 'explosive'];

    // COSSH Hazard Assessments - checkboxes - required.
    $form['cossh_hazard_assessments'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('COSSH Hazard Assessments'),
      '#options' => $hazard_options,
      '#required' => TRUE,
      '#description' => $this->t('The COSHH assessments which need to be considered when handling fertilisers. Select all that apply. The list can be expanded or amended in the Log categories taxonomy.'),
      '#weight' => ++$weight,
    ];

    // PPE.
    $ppe_option_values = [
      'Face sheild',
      'Coveralls',
      'Gloves',
      'Apron',
    ];
    $ppe_option_values_options = array_combine($ppe_option_values, $ppe_option_values);

    $form['ppe'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('PPE'),
      '#options' => $ppe_option_values_options,
      '#description' => $this->t('The protective clothing and equipment required for a specific job. Select all that apply to comonfirm they have been used. The list can be expanded or amended in the Log Categories taxonomy.'),
      '#weight' => ++$weight,
    ];

    // Knapsack Operator checklist - checkboxes - required.
    $form['knapsack_operator_checklist'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Knapsack operator checklist'),
      '#options' => ['completed' => 'Completed'],
      '#required' => FALSE,
      '#description' => $this->t('An additional set of Health and Safety checks speciffically for knapsack spraying which need to be marked off by the operator, as per Red Tracktor Guidlines.'),
      '#weight' => ++$weight,
    ];

    // Plant growth stage.
    $form['plant_growth_stage'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Plant growth stage'),
      '#required' => FALSE,
      '#description' => $this->t('The plant growth stage when the product was applied.'),
      '#weight' => ++$weight,
    ];

    // Spray nozzle options.
    $spray_nozzle_options = $this->getGroupMemberOptions(['Spray Nozzles'], ['equipment']);

    $form['spray_nozzle'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Type of nozzle'),
      '#options' => $spray_nozzle_options,
      '#description' => $this->t('The type of spray nozzle used, where relevant.'),
      '#weight' => ++$weight,
    ];

    // Wind speed (kph).
    $form['wind_speed'] = [
      '#type' => 'number',
      '#title' => $this->t('Wind speed (kph)'),
      '#input_group' => TRUE,
      '#field_suffix' => $this->t('kph'),
      '#required' => TRUE,
      '#description' => $this->t('The maximum wind speed during spraying.'),
      '#weight' => ++$weight,
    ];

    // Wind direction.
    $form['wind_direction'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Wind direction'),
      '#required' => TRUE,
      '#description' => $this->t('The dominant wind direction during spraying.'),
      '#weight' => ++$weight,
    ];

    // Temperature (Degrees C).
    $form['temperature'] = [
      '#type' => 'number',
      '#title' => $this->t('Temperature (C)'),
      '#field_suffix' => $this->t('C'),
      '#required' => TRUE,
      '#description' => $this->t('The average temperature during spraying.'),
      '#weight' => ++$weight,
    ];

    // Weather types.
    $weather_types = [
      '',
      'Cloudy',
      'Partially cloudy',
      'Clear',
      'Dry',
      'Light rain',
      'Heavy rain',
      'Snow',
      'Ice',
      'Frost',
      'Thunderstoms',
    ];
    $weather_types_options = array_combine($weather_types, $weather_types);

    // Weather.
    $form['weather'] = [
      '#type' => 'select',
      '#title' => $this->t('Weather'),
      '#options' => $weather_types_options,
      '#required' => TRUE,
      '#description' => $this->t('The dominant weather conditions during spraying.'),
      '#weight' => ++$weight,
    ];

    // Speed driven.
    $form['speed_driven'] = [
      '#type' => 'number',
      '#title' => $this->t('Speed driven'),
      '#required' => FALSE,
      '#description' => $this->t('The travelling speed when spraying, where relevant.'),
      '#weight' => ++$weight,
    ];

    // Speed driven units options.
    $speed_driven_units_options = [
      'mph' => 'mph',
      'kmh' => 'km/h',
    ];

    // Speed driven units.
    $form['speed_driven_units'] = [
      '#type' => 'radios',
      '#title' => $this->t('Speed driven units'),
      '#options' => $speed_driven_units_options,
      '#description' => $this->t('The speed driven units.'),
      '#weight' => ++$weight,
    ];

    // Pressure - number.
    $form['pressure'] = [
      '#type' => 'number',
      '#title' => $this->t('Pressure'),
      '#field_suffix' => $this->t('bar'),
      '#required' => FALSE,
      '#description' => $this->t('The water pressure used when applying the product, where relevant.'),
      '#weight' => ++$weight,
    ];

    // Tank mix ID.
    $form['tank_mix_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tank mix ID'),
      '#required' => FALSE,
      '#description' => $this->t('The record number for this tank mix. This is essential information if the same tank mix is applied over multiple crops or experiments.'),
      '#weight' => ++$weight,
    ];

    // Tank volume remaining.
    $form['tank_volume_remaining'] = [
      '#type' => 'number',
      '#title' => $this->t('Tank volume remaining'),
      '#required' => FALSE,
      '#description' => $this->t('If the full tank used enter zero. If not, estimate or calculate the remaining.'),
      '#weight' => ++$weight,
    ];

    // Tank volume remaining units options.
    $tank_volume_ramaining_units_options = [
      'l' => 'l',
      'gal' => 'gal',
    ];

    // Tank volume remaining units.
    $form['tank_volume_remaining_units'] = [
      '#type' => 'radios',
      '#title' => $this->t('Tank volume remaining units'),
      '#options' => $tank_volume_ramaining_units_options,
      '#description' => $this->t('The tank volume remaining units.'),
      '#weight' => ++$weight,
    ];

    // Equipment triple Rinsed.
    $form['rinsed'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Equipment tripple rinsed'),
      '#required' => TRUE,
      '#description' => $this->t('Select if the equipment was triple rinsed after the job was completed.'),
      '#weight' => ++$weight,
    ];

    // Equipment clear washed.
    $form['clear_washed'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Equipment clear washed'),
      '#required' => TRUE,
      '#description' => $this->t('Select if the equipment was clear washed after the job was completed.'),
      '#weight' => ++$weight,
    ];

    // COSSH Hazard Assessments - checkboxes - required - second instance.
    $form['cossh_hazard_assessments_2'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('COSSH Hazard Assessments'),
      '#options' => $hazard_options,
      '#required' => TRUE,
      '#description' => $this->t('The COSHH assessments which need to be considered when handling fertilisers. Select all that apply. The list can be expanded or amended in the Log categories taxonomy.'),
      '#weight' => ++$weight,
    ];

    // Fuel use units options.
    $fuel_use_units_options = [
      'l' => 'l',
      'gal' => 'gal',
    ];

    // Tank volume remaining units.
    $form['fuel_use_units'] = [
      '#type' => 'radios',
      '#title' => $this->t('Fuel use units'),
      '#options' => $fuel_use_units_options,
      '#description' => $this->t('The Fuel use units.'),
      '#weight' => ++$weight,
    ];

    // Seed labels - file picker - optional.
    // @todo Determine the final file upload location.
    $form['seed_labels'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Seed labels'),
      '#upload_location' => 'private://quick',
      '#upload_validators' => [
        'file_validate_extensions' => ['jpg jpeg'],
      ],
      '#required' => TRUE,
      '#description' => $this->t('Photograph(s) of the seed label taken prior to drilling or confirm the right seed batch and variety was used.'),
      '#weight' => ++$weight,
    ];

    return $form;
  }

}
