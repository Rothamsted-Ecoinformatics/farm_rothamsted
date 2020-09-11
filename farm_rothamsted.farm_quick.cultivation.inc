<?php

/**
 * @file
 * Rothamsted cultivation quick form.
 */

// Include common functions.
include_once('farm_rothamsted.farm_quick.common.inc');

/**
 * Cultivation quick form.
 */
function farm_rothamsted_cultivation_quick_form($form, &$form_state) {

  // Create a base Rothamsted quick form.
  $name = 'cultivation';
  $title = t('Cultivation Form');
  $equipment_group_names = ['Tractor Equipment', 'Cultivation Equipment'];
  farm_rothamsted_quick_form_base($name, $title, $form, $form_state, $equipment_group_names, FALSE);

  // Cultivation type.
  $cultivation_type_options = array(
    'Sub Tillage',
    'Ploughed Tillage',
    'Minimum Tillage',
  );
  $form[$name]['cultivation_type'] = array(
    '#type' => 'select',
    '#title' => t('Cultivation type'),
    '#options' => drupal_map_assoc($cultivation_type_options),
    '#required' => TRUE,
  );

  // Depth worked.
  $form[$name]['depth'] = array(
    '#type' => 'textfield',
    '#title' => t('Depth worked (centimeters)'),
    '#input_group' => TRUE,
    '#field_suffix' => t('centimeters'),
    '#element_validate' => array('element_validate_number'),
    '#required' => TRUE,
    '#weight' => 11,
  );

  // Define direction options.
  $direction_options = array(
    '',
    'N',
    'NE',
    'E',
    'SE',
    'S',
    'SW',
    'W',
    'NW',
  );

  // Direction of work (driven).
  $form[$name]['direction'] = array(
    '#type' => 'select',
    '#title' => t('Direction of work (driven)'),
    '#options' => drupal_map_assoc($direction_options),
    '#weight' => 12,
  );

  // Plough thrown (if applicable).
  $form[$name]['thrown'] = array(
    '#type' => 'select',
    '#title' => t('Plough thrown (if applicable)'),
    '#options' => drupal_map_assoc($direction_options),
    '#weight' => 13,
  );

  // Return the form.
  return $form;
}

/**
 * Cultivation quick form submit.
 */
function farm_rothamsted_cultivation_quick_form_submit($form, &$form_state) {

  // Get the machine name.
  $name = $form_state['values']['name'];

  // Alias $form_state['values'][$name] for easier use.
  $form_values = array();
  if (!empty($form_state['values'][$name])) {
    $form_values = &$form_state['values'][$name];
  }

  // Get the timestamp.
  $timestamp = strtotime($form_values['date']);

  // Get the area from form state storage.
  $area = $form_state['storage']['area'];

  // The log type will be an activity.
  $log_type = 'farm_activity';

  // Initialize an empty measurements array.
  $measurements = array();

  // Add the cultivation depth.
  $cultivation_depth = array(
    'measure' => 'length',
    'value' => $form_values['depth'],
    'units' => 'cm',
  );
  $measurements[] = $cultivation_depth;

  // Load the selected cultivation type.
  $cultivation_type = $form_values['cultivation_type'];

  // Set log name.
  $args = array(
    '@qty' => $cultivation_depth['value'],
    '@units' => $cultivation_depth['units'],
    '@type' => $cultivation_type,
    '@area' => entity_label('taxonomy_term', $area),
  );
  $log_name = t('Cultivation: @type @qty @units in @area', $args);

  // Create a new farm quantity log.
  $log = farm_quantity_log_create($log_type, $log_name, $timestamp, TRUE, array(), $measurements);

  // Assemble extra notes.
  $extra_notes = array();

  // Add the direction of work to notes.
  if (!empty($form_values['direction'])) {
    $extra_notes[] = 'Direction of work (driven): ' . $form_values['direction'];
  }

  // Add the plough thrown to notes.
  if (!empty($form_values['thrown'])) {
    $extra_notes[] = 'Plough thrown: ' . $form_values['thrown'];
  }

  // Call the Rothamsted quick form helper function, which generates a log
  // metadata wrapper.
  $log_wrapper = farm_rothamsted_quick_form_base_submit_helper($log, $form_state, $extra_notes);

  // Save the log (via its wrapper).
  $log_wrapper->save();
}
