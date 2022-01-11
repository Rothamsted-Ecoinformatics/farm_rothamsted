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

    // Machinery checkboxes - required.
    $form['machinery']['#required'] = TRUE;

    // Require the operator field.
    $form['users']['#required'] = TRUE;

    // Allow date and time to be specified.
    $form['date']['#date_part_order'] = ['year', 'month', 'day', 'hour', 'minute'];

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
      '#title' => $this->t('Product'),
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
    $product_rate_unit_options = array_combine($product_rate_units , $product_rate_units);

    $form['product_rate_units'] = [
      '#type' => 'select',
      '#title' => $this->t('Units'),
      '#options' => $product_rate_unit_options,
      '#weight' => ++$weight,
    ];
    // ------------end of product area --------------------


    // @todo Number of chemicals.

    // Chemical units.
    $chemical_units = [
      '',
      'lt/ha',
      'kg/ha',
      'ml/ha',
      'grm/ha',
    ];
    $chemical_unit_options = array_combine($chemical_units, $chemical_units);

    // @todo Each chemical - units from hard coded list.
    $form['chemicals'][0]['units'] = [
      '#type' => 'select',
      '#title' => $this->t('Units'),
      '#options' => $chemical_unit_options,
    ];
    // @todo AJAX for each chemical.

    // Weather.
    $form['weather'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Weather'),
    ];

    // Recommendation number.
    $form['recommendation_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Recommendation number'),
      '#description' => $this->t('Please enter NA if there is no recommendation for this task.'),
      '#required' => TRUE,
    ];

    // Rinsed 3 times.
    $form['rinsed'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Rinsed 3 times'),
    ];

    // All clear washed.
    $form['clear_washed'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('All clear washed'),
    ];

    // Build justification options from the Spray Applications parent term.
    $justification_options = $this->getChildTermOptions('log_category', 'Justification/Target (Spray Applications)');

    // Justification/Target as log categories.
    $form['categories'] = [
      '#type' => 'select',
      '#title' => $this->t('Justification/Target'),
      '#options' => $justification_options,
      '#multiple' => TRUE,
      '#weight' => 16,
    ];

    // Assigned to.
    $form['assigned_to'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Assigned to'),
      '#description' => $this->t('The person setting up the task and specifiying the work that needs to be done.'),
      '#required' => TRUE,
      '#weight' => ++$weight,
    ];

    // -------------------- spray days -----------------------
    // @todo wrap in ajax - button to add another day

    // Volume sprayed.
    $form['volume_sprayed'] = [
      '#type' => 'number',
      '#title' => $this->t('Volume sprayed (L)'),
      '#input_group' => TRUE,
      '#field_suffix' => $this->t('L'),
      '#required' => TRUE,
      '#description' => $this->t('Spray volume'),
      '#weight' => ++$weight,
    ];

    // Area sprayed.
    $form['area_sprayed'] = [
      '#type' => 'number',
      '#title' => $this->t('Area sprayed (Ha)'),
      '#input_group' => TRUE,
      '#field_suffix' => $this->t('Ha'),
      '#required' => TRUE,
      '#description' => $this->t('The total area being sprayed.'),
      '#weight' => ++$weight,
    ];

    // Water used.
    $form['water_used'] = [
      '#type' => 'number',
      '#title' => $this->t('Water used'),
      '#input_group' => TRUE,
      '#field_suffix' => $this->t('l'),
      '#required' => TRUE,
      '#description' => $this->t('The total water used.'),
      '#weight' => ++$weight,
    ];

    // Spray day start time and date - date time picker - required.
    $form['spray_day_start_time_and_date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Operation start time and date'),
      '#description' => $this->t('The start date and time of the spray operation.'),
      '#required' => TRUE,
      '#weight' => ++$weight,
    ];

    // Spray day end time and date - date time picker - required.
    $form['spray_day_end_time_and_date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Operation end time and date'),
      '#description' => $this->t('The end date and time of the spray operation.'),
      '#required' => TRUE,
      '#weight' => ++$weight,
    ];

    // Completed - checkboxes - required.
    $form['completed'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Completed'),
      '#options' => ['completed' => 'Completed'],
      '#required' => TRUE,
      '#description' => $this->t('Was the work completed?'),
      '#weight' => ++$weight,
    ];

    // Spray nozzle options.
    $spray_nozzle_options = $this->getGroupMemberOptions(['Spray Nozzles'], ['equipment']);

    $form['spray_nozzle'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Type of nozzle'),
      '#options' => $spray_nozzle_options,
      '#weight' => ++$weight,
      '#description' => $this->t('The type of spray nozzle used, where relevant.'),
    ];

    // pressure - number
    $form['pressure'] = [
      '#type' => 'number',
      '#title' => $this->t('Pressure'),
      '#description' => $this->t('The water pressure used when applying the product, where relevant.'),
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
      '#input_group' => TRUE,
      '#field_suffix' => $this->t('C'),
      '#description' => $this->t('The average temperature during spraying.'),
      '#weight' => ++$weight,
    ];


    // ------------------end spray days -----------------------


    // Build hazard options.
    // @todo Determine way to define hazard options. See issue #64.
    $hazard_options = [];

    // COSSH Hazard Assessments - checkboxes - required.
    $form['cossh_hazard_assessments'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('COSSH Hazard Assessments'),
      '#options' => $hazard_options,
      '#required' => TRUE,
      '#description' => $this->t('The COSHH assessments which need to be considered when handling fertilisers. Select all that apply. The list can be expanded or amended in the Log categories taxonomy.'),
      '#weight' => ++$weight,
    ];

    // PPE
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
      '#title' => $this->t('Completed'),
      '#options' => ['completed' => 'Completed'],
      '#required' => TRUE,
      '#description' => $this->t('An additional set of Health and Safety checks speciffically for knapsack spraying which need to be marked off by the operator, as per Red Tracktor Guidlines.'),
      '#weight' => ++$weight,
    ];

    return $form;
  }

}
