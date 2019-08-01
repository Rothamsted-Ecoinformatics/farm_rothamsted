<?php

/**
 * @file
 * Rothamsted harvest quick form.
 */

// Include common functions.
include_once('farm_rothamsted.farm_quick.common.inc');

/**
 * Harvest quick form.
 */
function farm_rothamsted_harvest_quick_form($form, &$form_state) {

  // Create a base Rothamsted quick form.
  $name = 'harvest';
  $title = t('Harvest Form');
  farm_rothamsted_quick_form_base($name, $title, $form, $form_state);

  // Quantity measure (weight or volume).
  $form[$name]['quantity']['measure'] = array(
    '#type' => 'select',
    '#title' => t('Quantity measure'),
    '#options' => array(
      'weight' => t('Weight'),
      'volume' => t('Volume'),
    ),
  );

  // Quantity value.
  $form[$name]['quantity']['value'] = array(
    '#type' => 'textfield',
    '#title' => t('Quantity value'),
    '#element_validate' => array('element_validate_number'),
  );

  // Quantity units.
  $form[$name]['quantity']['units'] = array(
    '#type' => 'textfield',
    '#title' => t('Quantity units'),
    '#autocomplete_path' => 'taxonomy/autocomplete/field_farm_quantity_units',
  );

  // Return the form.
  return $form;
}

/**
 * Harvest quick form submit.
 */
function farm_rothamsted_harvest_quick_form_submit($form, &$form_state) {

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

  // The log type will be a harvest.
  $log_type = 'farm_harvest';

  // Initialize an empty measurements array.
  $measurements = array();

  // Add the harvest quantity.
  if (!empty($form_values['quantity']['value'])) {
    $measurements[] = array(
      'measure' => $form_values['quantity']['measure'],
      'value' => $form_values['quantity']['value'],
      'units' => $form_values['quantity']['units'],
    );
  }

  // Get the plantings referenced.
  $plantings = array();
  if (!empty($form_values['experiments']['plantings'])) {
    $planting_ids = array_values(array_filter($form_values['experiments']['plantings']));
    if (!empty($planting_ids)) {
      $plantings = entity_load('farm_asset', $planting_ids);
    }
  }

  // If plantings were selected, assemble a log name from them.
  if (!empty($plantings)) {
    $planting_labels = array();
    foreach ($plantings as $planting) {
      $planting_labels[] = entity_label('farm_asset', $planting);
    }
    $log_name = implode(', ', $planting_labels);
  }

  // Otherwise, use the area name in the log name.
  else {
    $log_name = entity_label('taxonomy_term', $area);
  }

  // Set log name.
  $args = array(
    '@log_name' => $log_name,
  );
  $log_name = t('Harvest @log_name', $args);

  // Create a new farm quantity log.
  $log = farm_quantity_log_create($log_type, $log_name, $timestamp, TRUE, array(), $measurements);

  // Call the Rothamsted quick form helper function, which generates a log
  // metadata wrapper.
  $log_wrapper = farm_rothamsted_quick_form_base_submit_helper($log, $form_state);

  // Save the log (via its wrapper).
  $log_wrapper->save();
}
