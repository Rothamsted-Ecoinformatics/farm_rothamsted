<?php

/**
 * @file
 * Rothamsted fertilising quick form.
 */

// Include common functions.
include_once('farm_rothamsted.farm_quick.common.inc');

/**
 * Fertiliser quick form.
 */
function farm_rothamsted_fertiliser_quick_form($form, &$form_state) {

  // Create a base Rothamsted quick form.
  $name = 'fertiliser';
  $title = t('Fertiliser Application Form');
  farm_rothamsted_quick_form_base($name, $title, $form, $form_state);

  // Load fertiliser options (sub-terms of the "Fertiliser" material term).
  $fertiliser_terms = taxonomy_get_term_by_name('Fertilisers', 'farm_materials');
  $fertiliser_term = reset($fertiliser_terms);
  $fertiliser_options = array();
  if (!empty($fertiliser_term->tid)) {
    $fertilisers = taxonomy_get_children($fertiliser_term->tid);
    if (!empty($fertilisers)) {
      foreach ($fertilisers as $fertiliser) {
        $fertiliser_options[$fertiliser->tid] = entity_label('taxonomy_term', $fertiliser);
      }
    }
  }

  // Fertiliser select list.
  $form[$name]['fertiliser'] = array(
    '#type' => 'select',
    '#title' => t('Fertiliser'),
    '#options' => $fertiliser_options,
    '#required' => TRUE,
  );

  // Fertiliser rate.
  $form[$name]['rate'] = array(
    '#type' => 'textfield',
    '#title' => t('Rate of application (kg/ha)'),
    '#input_group' => TRUE,
    '#field_suffix' => t('kg/ha'),
    '#element_validate' => array('element_validate_number'),
    '#required' => TRUE,
  );

  // Return the form.
  return $form;
}

/**
 * Fertiliser quick form submit.
 */
function farm_rothamsted_fertiliser_quick_form_submit($form, &$form_state) {

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

  // The log type will be an input.
  $log_type = 'farm_input';

  // Initialize an empty measurements array.
  $measurements = array();

  // Add the rate of application.
  $fertiliser_rate = array(
    'measure' => 'rate',
    'value' => $form_values['rate'],
    'units' => 'kg/ha',
  );
  $measurements[] = $fertiliser_rate;

  // Load the selected material term.
  $material_id = $form_values['fertiliser'];
  $material_term = taxonomy_term_load($material_id);
  $material_label = entity_label('taxonomy_term', $material_term);

  // Set log name.
  $args = array(
    '@qty' => $fertiliser_rate['value'],
    '@units' => $fertiliser_rate['units'],
    '@material' => $material_label,
    '@area' => entity_label('taxonomy_term', $area),
  );
  $log_name = t('Fertiliser application: @qty @units @material into @area', $args);

  // Create a new farm quantity log.
  $log = farm_quantity_log_create($log_type, $log_name, $timestamp, TRUE, array(), $measurements);

  // Call the Rothamsted quick form helper function, which generates a log
  // metadata wrapper.
  $log_wrapper = farm_rothamsted_quick_form_base_submit_helper($log, $form_state);

  // Add the material.
  $log_wrapper->field_farm_material[] = $material_term;

  // Set the purpose to "Fertiliser".
  $log_wrapper->field_farm_input_purpose->set(t('Fertiliser'));

  // Save the log (via its wrapper).
  $log_wrapper->save();
}
