<?php

/**
 * @file
 * Rothamsted drilling quick form.
 */

// Include common functions.
include_once('farm_rothamsted.farm_quick.common.inc');

/**
 * Drilling quick form.
 */
function farm_rothamsted_drilling_quick_form($form, &$form_state) {

  // Create a base Rothamsted quick form.
  $name = 'drilling';
  $title = t('Drilling Form');
  $equipment_group_names = ['Tractor Equipment', 'Drilling Equipment'];
  farm_rothamsted_quick_form_base($name, $title, $form, $form_state, $equipment_group_names, FALSE);

  // Alias $form_state['values'][$name] for easier use.
  $form_values = array();
  if (!empty($form_state['values'][$name])) {
    $form_values = &$form_state['values'][$name];
  }

  // Load the crop/varieties taxonomy and create a generic query that can be
  // used to load parent and child terms.
  $vocabulary = taxonomy_vocabulary_machine_name_load('farm_crops');
  $crop_query = 'SELECT td.tid FROM {taxonomy_term_data} td LEFT JOIN {taxonomy_term_hierarchy} th ON td.tid = th.tid WHERE td.vid = :vid AND th.parent = :pid';

  // Crop/variety options. Display a dropdown of top-level crop/varieties.
  $result = db_query($crop_query, array(':vid' => $vocabulary->vid, ':pid' => 0));
  $parent_options = array();
  foreach ($result as $row) {
    if (!empty($row->tid)) {
      $term = taxonomy_term_load($row->tid);
      $parent_options[$row->tid] = entity_label('taxonomy_term', $term);
    }
  }
  $form[$name]['crop']['parent'] = array(
    '#type' => 'select',
    '#title' => t('Crop'),
    '#options' => $parent_options,
    '#required' => TRUE,
    '#ajax' => array(
      'callback' => 'farm_rothamsted_drilling_quick_form_variety_ajax',
      'wrapper' => 'crop-variety'
    ),
  );

  // If a parent crop is selected, show a list of child options.
  $form[$name]['crop']['variety'] = array(
    '#type' => 'container',
    '#title' => t('Variety'),
    '#prefix' => '<div id="crop-variety">',
    '#suffix' => '</div>',
  );
  if (!empty($form_values['crop']['parent'])) {
    $result = db_query($crop_query, array(':vid' => $vocabulary->vid, ':pid' => $form_values['crop']['parent']));
    $child_options = array();
    foreach ($result as $row) {
      if (!empty($row->tid)) {
        $term = taxonomy_term_load($row->tid);
        $child_options[$row->tid] = entity_label('taxonomy_term', $term);
      }
    }
    if (!empty($child_options)) {
      $form[$name]['crop']['variety']['#type'] = 'select';
      $form[$name]['crop']['variety']['#options'] = $child_options;
    }
  }

  // RES lot number.
  $form[$name]['lot_number'] = array(
    '#type' => 'textfield',
    '#title' => t('RES lot number'),
  );

  // Thousand grain weight.
  $form[$name]['tgw'] = array(
    '#type' => 'textfield',
    '#title' => t('Thousand grain weight (TGW)'),
    '#input_group' => TRUE,
    '#field_suffix' => t('grams'),
    '#element_validate' => array('element_validate_number'),
  );

  // Seed rate (SM2).
  $form[$name]['rate_sm2'] = array(
    '#type' => 'textfield',
    '#title' => t('Seed rate (SM<sup>2</sup>)'),
    '#input_group' => TRUE,
    '#field_suffix' => t('SM<sup>2</sup>'),
    '#element_validate' => array('element_validate_number'),
  );

  // Seed rate (Kg/Ha).
  $form[$name]['rate_kgha'] = array(
    '#type' => 'textfield',
    '#title' => t('Seed rate (Kg/Ha)'),
    '#input_group' => TRUE,
    '#field_suffix' => t('Kg/Ha'),
    '#element_validate' => array('element_validate_number'),
  );

  // Return the form.
  return $form;
}

/**
 * Drilling quick form varieties ajax callback.
 */
function farm_rothamsted_drilling_quick_form_variety_ajax($form, &$form_state) {
  return $form['drilling']['crop']['variety'];
}

/**
 * Drilling quick form submit.
 */
function farm_rothamsted_drilling_quick_form_submit($form, &$form_state) {

  // Get the machine name.
  $name = $form_state['values']['name'];

  // Alias $form_state['values'][$name] for easier use.
  $form_values = array();
  if (!empty($form_state['values'][$name])) {
    $form_values = &$form_state['values'][$name];
  }

  // Load the selected crop/variety.
  if (!empty($form_values['crop']['variety'])) {
    $crop_id = $form_values['crop']['variety'];
  }
  else {
    $crop_id = $form_values['crop']['parent'];
  }
  $crop = taxonomy_term_load($crop_id);

  // Get the crop label.
  $crop_label = entity_label('taxonomy_term', $crop);

  // Get the area from form state storage.
  $area = $form_state['storage']['area'];

  // Get the area label.
  $area_label = entity_label('taxonomy_term', $area);

  // Get the timestamp.
  $timestamp = strtotime($form_values['date']);

  // Generate a planting name. If a planting with the same name already exists,
  // add a number to the end.
  $planting_name = date('Y', $timestamp) . ' ' . $area_label . ' ' . $crop_label;
  $query = "SELECT * FROM {farm_asset} WHERE type = 'planting' AND name = :planting_name";
  $check_planting_name = $planting_name;
  $i = 1;
  while (!empty(db_query($query, array(':planting_name' => $check_planting_name))->fetchField())) {
    $i++;
    $check_planting_name = $planting_name . ' (' . $i . ')';
  }
  $planting_name = $check_planting_name;

  // Create a new planting asset.
  $values = array(
    'type' => 'planting',
    'name' => $planting_name,
  );
  $planting_asset = entity_create('farm_asset', $values);
  $planting_wrapper = entity_metadata_wrapper('farm_asset', $planting_asset);

  // Add the crop.
  $planting_wrapper->field_farm_crop[] = $crop;

  // Save the planting.
  $planting_wrapper->save();

  // Link the asset to this quick form.
  if (function_exists('farm_quick_entity_link')) {
    farm_quick_entity_link('farm_rothamsted_drilling_quick_form', 'farm_asset', $planting_asset);
  }

  // Set a message.
  $label = entity_label('farm_asset', $planting_asset);
  $uri = entity_uri('farm_asset', $planting_asset);
  drupal_set_message('Planting created: ' . l($label, $uri['path']));

  // Add the planting to the selected experiment group(s).
  if (!empty($form_values['experiments']['experiments'])) {
    $group_ids = array_values(array_filter($form_values['experiments']['experiments']));
    $groups = entity_load('farm_asset', $group_ids);
    farm_group_membership_set($planting_asset, $groups, $timestamp);
  }

  // Add the planting ID to $form_values['experiments']['plantings'], so that it
  // is automatically linked to the seeding log by
  // farm_rothamsted_quick_form_base_submit_helper().
  $form_values['experiments']['plantings'] = array(
    $planting_asset->id => $planting_asset->id,
  );

  // The log type will be an input.
  $log_type = 'farm_seeding';

  // Initialize an empty measurements array.
  $measurements = array();

  // Add the thousand grain weight (TGW).
  if (!empty($form_values['tgw'])) {
    $tgw = array(
      'measure' => 'weight',
      'value' => $form_values['tgw'],
      'units' => 'grams',
      'label' => 'Thousand grain weight (TGW)',
    );
    $measurements[] = $tgw;
  }

  // Add the seed rate (SM2).
  if (!empty($form_values['rate_sm2'])) {
    $rate_sm2 = array(
      'measure' => 'rate',
      'value' => $form_values['rate_sm2'],
      'units' => 'SM2',
      'label' => 'Seed rate (SM2)',
    );
    $measurements[] = $rate_sm2;
  }

  // Add the seed rate (Kg/Ha).
  if (!empty($form_values['rate_kgha'])) {
    $rate_kgha = array(
      'measure' => 'rate',
      'value' => $form_values['rate_kgha'],
      'units' => 'Kg/Ha',
      'label' => 'Seed rate (Kg/Ha)',
    );
    $measurements[] = $rate_kgha;
  }

  // Set log name.
  $args = array(
    '@crop' => entity_label('taxonomy_term', $crop),
    '@area' => entity_label('taxonomy_term', $area),
  );
  $log_name = t('Drilling: @crop in @area', $args);

  // Create a new farm quantity log.
  $log = farm_quantity_log_create($log_type, $log_name, $timestamp, TRUE, array(), $measurements);

  // Call the Rothamsted quick form helper function, which generates a log
  // metadata wrapper.
  $log_wrapper = farm_rothamsted_quick_form_base_submit_helper($log, $form_state);

  // Set lot number.
  if (!empty($form_values['lot_number'])) {
    $log_wrapper->field_farm_lot_number->set(check_plain($form_values['lot_number']));
  }

  // Save the log (via its wrapper).
  $log_wrapper->save();
}
