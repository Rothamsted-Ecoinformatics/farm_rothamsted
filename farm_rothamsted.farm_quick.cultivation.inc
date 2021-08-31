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
  // This quickform is used for more general "operations" records,
  // but we maintain the existing quickform ID as "cultivation".
  $name = 'cultivation';

  $title = t('Operations Form');
  $equipment_group_names = ['Tractor Equipment', 'Cultivation Equipment'];
  farm_rothamsted_quick_form_base($name, $title, $form, $form_state, $equipment_group_names, FALSE);

  // Build associate arrays of task options.
  $grass_harvest_options = drupal_map_assoc(array(
    'Mowing',
    'Silage pick up',
    'Bailing',
    'Tedding/Spreading grass',
    'Rowing up',
    'Hay turning',
  ));
  $grassland_options = drupal_map_assoc(array(
    'Flat roll',
    'Chain harrow',
    'Aeration',
    'Topping',
  ));
  $cultivation_options = drupal_map_assoc(array(
    'Plough',
    'Power harrow',
    'Rolling',
    'Rotavate',
    'Sub soil/ripping',
    'Mole plough',
    'Cultivate/level',
    'Hoeing',
    'Hand weeding',
  ));
  $other_options = drupal_map_assoc(array(
    'Hedge trimming',
    'Drain trim',
    'Drain burn',
    'Irrigation',
    'Treatment',
  ));

  // Combine all options into option groups.
  $operation_task_options = array(
    'Grass harvest' => $grass_harvest_options,
    'Grassland maintenance' => $grassland_options,
    'Cultivations' => $cultivation_options,
    'Other' => $other_options,
  );

  // Add a select element for the operation task.
  $form[$name]['operation_task'] = array(
    '#type' => 'select',
    '#title' => t('Task'),
    '#options' => $operation_task_options,
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

  // Load the selected operation task.
  $operation_task = $form_values['operation_task'];

  // Set log name.
  $args = array(
    '@qty' => $cultivation_depth['value'],
    '@units' => $cultivation_depth['units'],
    '@type' => $operation_task,
    '@area' => entity_label('taxonomy_term', $area),
  );
  $log_name = t('Operation: @type @qty @units in @area', $args);

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
