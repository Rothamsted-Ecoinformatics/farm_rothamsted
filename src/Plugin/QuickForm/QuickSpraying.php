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
    $form = parent::buildForm($form, $form_state);

    // Require the operator field.
    $form['users']['#required'] = TRUE;

    // Allow date and time to be specified.
    $form['date']['#date_part_order'] = ['year', 'month', 'day', 'hour', 'minute'];

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

    // Volume sprayed.
    $form['volume_sprayed'] = [
      '#type' => 'number',
      '#title' => $this->t('Volume sprayed (L)'),
      '#input_group' => TRUE,
      '#field_suffix' => $this->t('L'),
      '#required' => TRUE,
    ];

    // Area sprayed.
    $form['area_sprayed'] = [
      '#type' => 'number',
      '#title' => $this->t('Area sprayed (Ha)'),
      '#input_group' => TRUE,
      '#field_suffix' => $this->t('Ha'),
      '#required' => TRUE,
    ];

    // Wind speed (kph).
    $form['wind_speed'] = [
      '#type' => 'number',
      '#title' => $this->t('Wind speed (kph)'),
      '#input_group' => TRUE,
      '#field_suffix' => $this->t('kph'),
      '#required' => TRUE,
    ];

    // Wind direction.
    $form['wind_direction'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Wind direction'),
      '#required' => TRUE,
    ];

    // Weather.
    $form['weather'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Weather'),
    ];

    // Temperature (Degrees C).
    $form['temperature'] = [
      '#type' => 'number',
      '#title' => $this->t('Temperature (C)'),
      '#input_group' => TRUE,
      '#field_suffix' => $this->t('C'),
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

    // Spray nozzle options.
    $spray_nozzle_options = $this->getGroupMemberOptions(['Spray Nozzles'], ['equipment']);
    $form['spray_nozzle'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Type of nozzle'),
      '#options' => $spray_nozzle_options,
      '#weight' => 9,
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

    return $form;
  }

}
