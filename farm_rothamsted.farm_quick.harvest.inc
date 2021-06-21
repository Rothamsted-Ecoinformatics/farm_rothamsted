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
  $equipment_group_names = ['Tractor Equipment', 'Harvest Machinery Equipment'];
  farm_rothamsted_quick_form_base($name, $title, $form, $form_state, $equipment_group_names);

  // Number of quantities to create.
  $form[$name]['quantity']['count'] = array(
    '#type' => 'select',
    '#title' => t('How many quantities are associated with this harvest?'),
    '#options' => drupal_map_assoc(range(1, 5)),
    '#default_value' => 1,
    '#ajax' => array(
      'callback' => 'farm_rothamsted_harvest_quick_form_quantities_ajax',
      'wrapper' => 'farm-rothamsted-harvest-quantities',
    ),
  );

  // Create a wrapper around all quantity fields, for AJAX replacement.
  $form[$name]['quantity']['quantities'] = array(
    '#prefix' => '<div id="farm-rothamsted-harvest-quantities">',
    '#suffix' => '</div>',
  );

  // Add fields for each quantity.
  $quantities = 1;
  if (!empty($form_state['values'][$name]['quantity']['count'])) {
    $quantities = $form_state['values'][$name]['quantity']['count'];
  }
  for ($i = 0; $i < $quantities; $i++) {

    // Fieldset for each quantity.
    $form[$name]['quantity']['quantities'][$i] = array(
      '#type' => 'fieldset',
      '#title' => t('Quantity @number', array('@number' => $i + 1)),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );

    // Quantity measure (weight or volume).
    $form[$name]['quantity']['quantities'][$i]['measure'] = array(
      '#type' => 'select',
      '#title' => t('Quantity measure'),
      '#options' => array(
        'weight' => t('Weight'),
        'volume' => t('Volume'),
      ),
    );

    // Quantity value.
    $form[$name]['quantity']['quantities'][$i]['value'] = array(
      '#type' => 'textfield',
      '#title' => t('Quantity value'),
      '#element_validate' => array('element_validate_number'),
    );

    // Quantity units.
    $form[$name]['quantity']['quantities'][$i]['units'] = array(
      '#type' => 'textfield',
      '#title' => t('Quantity units'),
      '#autocomplete_path' => 'taxonomy/autocomplete/field_farm_quantity_units',
    );

    // Quantity label.
    $form[$name]['quantity']['quantities'][$i]['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label'),
    );
  }

  // Return the form.
  return $form;
}

/**
 * Form ajax function for harvest quick form quantities.
 */
function farm_rothamsted_harvest_quick_form_quantities_ajax($form, &$form_state) {
  return $form['harvest']['quantity']['quantities'];
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

  // Add the harvest quantities.
  foreach ($form_values['quantity']['quantities'] as $quantity) {
    $measurements[] = array(
      'measure' => $quantity['measure'],
      'value' => $quantity['value'],
      'units' => $quantity['units'],
      'label' => $quantity['label'],
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
