<?php

/**
 * @file
 * Rothamsted spraying quick form.
 */

// Include common functions.
include_once('farm_rothamsted.farm_quick.common.inc');

/**
 * Spraying quick form.
 */
function farm_rothamsted_spraying_quick_form($form, &$form_state) {

  // Create a base Rothamsted quick form.
  $name = 'spraying';
  $title = t('Spraying Form');
  farm_rothamsted_quick_form_base($name, $title, $form, $form_state);

  // Chemicals fieldset.
  $form[$name]['chemicals'] = array(
    '#type' => 'fieldset',
    '#title' => t('Chemical(s)'),
    '#theme' => 'farm_rothamsted_spraying_chemicals_fieldset',
    '#prefix' => '<div id="chemicals">',
    '#suffix' => '</div>',
  );

  // Keep track of how many sets of chemical fields to show.
  if (empty($form_state['storage']['chemicals'])) {
    $form_state['storage']['chemicals'] = 1;
  }

  // Build fields for the chemicals.
  for ($i = 0; $i < $form_state['storage']['chemicals']; $i++) {

    // Chemical name.
    $form[$name]['chemicals'][$i]['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#autocomplete_path' => 'taxonomy/autocomplete/field_farm_material',
    );

    // Chemical quantity.
    $form[$name]['chemicals'][$i]['qty'] = array(
      '#type' => 'textfield',
      '#title' => t('Quantity'),
      '#element_validate' => array('element_validate_number'),
    );

    // Chemical units.
    $chemical_unit_options = array(
      '',
      'lt/ha',
      'kg/ha',
      'ml/ha',
      'grm/ha',
    );
    $form[$name]['chemicals'][$i]['units'] = array(
      '#type' => 'select',
      '#title' => t('Units'),
      '#options' => drupal_map_assoc($chemical_unit_options),
    );
  }

  // Button to add a chemical.
  $form[$name]['chemicals']['add'] = array(
    '#type' => 'submit',
    '#value' => t('Add another chemical'),
    '#submit' => array('farm_rothamsted_spraying_quick_form_add_chemical'),
    '#limit_validation_errors' => array(),
    '#ajax' => array(
      'callback' => 'farm_rothamsted_spraying_quick_form_chemicals_ajax',
      'wrapper' => 'chemicals',
    ),
  );

  // Volume sprayed.
  $form[$name]['volume_sprayed'] = array(
    '#type' => 'textfield',
    '#title' => t('Volume sprayed (L)'),
    '#input_group' => TRUE,
    '#field_suffix' => t('L'),
    '#element_validate' => array('element_validate_number'),
  );

  // Area sprayed.
  $form[$name]['area_sprayed'] = array(
    '#type' => 'textfield',
    '#title' => t('Area sprayed (Ha)'),
    '#input_group' => TRUE,
    '#field_suffix' => t('Ha'),
    '#element_validate' => array('element_validate_number'),
  );

  // Wind speed (mph).
  $form[$name]['wind_speed'] = array(
    '#type' => 'textfield',
    '#title' => t('Wind speed (mph)'),
    '#input_group' => TRUE,
    '#field_suffix' => t('mph'),
    '#element_validate' => array('element_validate_number'),
  );

  // Wind direction.
  $form[$name]['wind_direction'] = array(
    '#type' => 'textfield',
    '#title' => t('Wind direction'),
  );

  // Weather.
  $form[$name]['weather'] = array(
    '#type' => 'textfield',
    '#title' => t('Weather'),
  );

  // Temperature (Degrees C).
  $form[$name]['temperature'] = array(
    '#type' => 'textfield',
    '#title' => t('Temperature (C)'),
    '#input_group' => TRUE,
    '#field_suffix' => t('C'),
    '#element_validate' => array('element_validate_number'),
  );

  // Rinsed 3 times.
  $form[$name]['rinsed'] = array(
    '#type' => 'checkbox',
    '#title' => t('Rinsed 3 times'),
  );

  // All clear washed.
  $form[$name]['clear_washed'] = array(
    '#type' => 'checkbox',
    '#title' => t('All clear washed'),
  );

  // Add validation.
  $form['#validate'][] = 'farm_rothamsted_spraying_quick_form_validate';

  // Return the form.
  return $form;
}

/**
 * Add a set of chemical fields to the form.
 */
function farm_rothamsted_spraying_quick_form_add_chemical($form, &$form_state) {
  $form_state['storage']['chemicals']++;
  $form_state['rebuild'] = TRUE;
}

/**
 * Spraying quick form chemicals ajax callback.
 */
function farm_rothamsted_spraying_quick_form_chemicals_ajax($form, &$form_state) {
  return $form['spraying']['chemicals'];
}

/**
 * Spraying quick form validate.
 */
function farm_rothamsted_spraying_quick_form_validate($form, &$form_state) {

  // Get the machine name.
  $name = $form_state['values']['name'];

  // Alias $form_state['values'][$name] for easier use.
  $form_values = array();
  if (!empty($form_state['values'][$name])) {
    $form_values = &$form_state['values'][$name];
  }

  // Iterate through chemicals.
  foreach (element_children($form_values['chemicals']) as $key) {

    // Skip non-numeric keys (eg: the "add" button).
    if (!is_numeric($key)) {
      continue;
    }

    // If a chemical name, qty, or units are entered, all three are required.
    $item = &$form_values['chemicals'][$key];
    if (!empty($item['name']) || !empty($item['qty']) || !empty($item['units'])) {
      $name_prefix = $name . '][chemicals][' . $key . '][';
      if (empty($item['name'])) {
        form_set_error($name_prefix . 'name', t('Chemical name is required.'));
      }
      if (empty($item['qty'])) {
        form_set_error($name_prefix . 'qty', t('Chemical quantity is required.'));
      }
      if (empty($item['units'])) {
        form_set_error($name_prefix . 'units', t('Chemical units are required.'));
      }
    }
  }
}

/**
 * Spraying quick form submit.
 */
function farm_rothamsted_spraying_quick_form_submit($form, &$form_state) {

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

  // Iterate through the chemicals. Assemble a list of material terms, and
  // measurements for the log.
  $material_terms = array();
  $material_labels = array();
  $measurements = array();
  foreach (element_children($form_values['chemicals']) as $key) {

    // Skip non-numeric keys (eg: the "add" button).
    if (!is_numeric($key)) {
      continue;
    }

    // Alias the chemical fields.
    $chemical = &$form_values['chemicals'][$key];

    // If the name is empty, skip.
    if (empty($chemical['name'])) {
      continue;
    }

    // Load the material (this will create a new one if it doesn't exist).
    $material = farm_term($chemical['name'], 'farm_materials');

    // If a term was not loaded, skip it.
    if (empty($material)) {
      continue;
    }

    // Add the material to the array of terms.
    $material_terms[] = $material;

    // Add the material's label to the array of labels.
    $material_label = entity_label('taxonomy_term', $material);
    $material_labels[] = $material_label;

    // Assemble a quantity measurement.
    $measurement = array(
      'measure' => 'rate',
      'label' => $material_label,
    );

    // If the quantity is set, add it to the measurement.
    if (!empty($chemical['qty'])) {
      $measurement['value'] = $chemical['qty'];
    }

    // If the units are set, add it to the measurement.
    if (!empty($chemical['units'])) {
      $measurement['units'] = $chemical['units'];
    }

    // Add the measurement to the array.
    $measurements[] = $measurement;
  }

  // Add the volume sprayed.
  if (!empty($form_values['volume_sprayed'])) {
    $measurements[] = array(
      'measure' => 'volume',
      'value' => $form_values['volume_sprayed'],
      'units' => 'liters',
      'label' => t('Volume sprayed'),
    );
  }

  // Add the area sprayed.
  if (!empty($form_values['area_sprayed'])) {
    $measurements[] = array(
      'measure' => 'area',
      'value' => $form_values['area_sprayed'],
      'units' => 'hectares',
      'label' => t('Area sprayed'),
    );
  }

  // Set log name.
  $material_labels = implode(', ', $material_labels);
  $args = array(
    '@materials' => $material_labels,
    '@area' => entity_label('taxonomy_term', $area),
  );
  $log_name = t('Spraying: @materials in @area', $args);

  // Create a new farm quantity log.
  $log = farm_quantity_log_create($log_type, $log_name, $timestamp, TRUE, array(), $measurements);

  // Add to notes: wind speed, wind direction, weather, temperature, rinsed, all
  // clear washed.
  $extra_notes = array();
  if (!empty($form_values['wind_speed']) || !empty($form_values['wind_direction'])) {
    $note = 'Wind:';
    if (!empty($form_values['wind_speed'])) {
      $note .= ' ' . $form_values['wind_speed'] . ' mph';
    }
    if (!empty($form_values['wind_direction'])) {
      $note .= ' ' . $form_values['wind_direction'];
    }
    $extra_notes[] = $note;
  }
  if (!empty($form_values['weather'])) {
    $extra_notes[] = 'Weather: ' . $form_values['weather'];
  }
  if (!empty($form_values['temperature'])) {
    $extra_notes[] = 'Temperature: ' . $form_values['temperature'] . ' C';
  }
  if (!empty($form_values['rinsed'])) {
    $extra_notes[] = 'Rinsed 3 times';
  }
  if (!empty($form_values['clear_washed'])) {
    $extra_notes[] = 'All clear washed';
  }

  // Call the Rothamsted quick form helper function, which generates a log
  // metadata wrapper.
  $log_wrapper = farm_rothamsted_quick_form_base_submit_helper($log, $form_state, $extra_notes);

  // Add the materials.
  $log_wrapper->field_farm_material = $material_terms;

  // Set the purpose to "Spraying".
  $log_wrapper->field_farm_input_purpose->set(t('Spraying'));

  // Save the log (via its wrapper).
  $log_wrapper->save();
}

/**
 * Spraying quick form theme function.
 */
function theme_farm_rothamsted_spraying_chemicals_fieldset(&$vars) {
  $fieldset = $vars['fieldset'];
  $output = '';

  // Render table for chemicals.
  if (!empty(element_children($fieldset))) {

    // Define the table header.
    $header = array(
      t('Material name'),
      t('Quantity'),
      t('Units'),
    );

    // Start an empty array of table rows.
    $rows = array();

    // Iterate through the chemicals.
    foreach (element_children($fieldset) as $key) {

      // Skip non-numeric keys (eg: the "add" button).
      if (!is_numeric($key)) {
        continue;
      }

      // Get the chemical.
      $item =& $fieldset[$key];

      // Hide the titles.
      $item['name']['#title_display'] = 'invisible';
      $item['qty']['#title_display'] = 'invisible';
      $item['units']['#title_display'] = 'invisible';

      // Assemble the row.
      $rows[] = array(
        'data' => array(
          drupal_render($item['name']),
          drupal_render($item['qty']),
          drupal_render($item['units']),
        ),
      );
    }

    // Render the table.
    $output .= theme('table', array('header' => $header, 'rows' => $rows));
  }

  // Render any remaining elements.
  $output .= drupal_render_children($fieldset);
  return $output;
}
