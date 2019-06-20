<?php

/**
 * @file
 * Rothamsted fertilising quick form.
 */

/**
 * Fertiliser quick form.
 */
function farm_rothamsted_fertiliser_quick_form($form, &$form_state) {

  // Alias $form_state['values']['fertiliser'] for easier use.
  $form_values = array();
  if (!empty($form_state['values']['fertiliser'])) {
    $form_values = &$form_state['values']['fertiliser'];
  }

  // Wrapper fieldset.
  $form['fertiliser'] = array(
    '#type' => 'fieldset',
    '#title' => t('Fertiliser Application Form'),
    '#tree' => TRUE,
  );

  // Area selection (autocomplete).
  $form['fertiliser']['area'] = array(
    '#type' => 'textfield',
    '#title' => t( 'Area'),
    '#description' => t('Select the area that fertiliser is being applied to.'),
    '#autocomplete_path' => 'taxonomy/autocomplete/field_farm_area',
    '#required' => TRUE,
    '#ajax' => array(
      'callback' => 'farm_rothamsted_fertiliser_quick_form_experiments_ajax',
      'wrapper' => 'experiments',
    ),
  );

  // Create a container for experiments.
  $form['fertiliser']['experiments'] = array(
    '#type' => 'container',
    '#prefix' => '<div id="experiments">',
    '#suffix' => '</div>',
  );

  // If an area is selected, load experiments in it (active group assets).
  if (!empty($form_values['area'])) {

    // Load the area.
    $area = farm_term(check_plain($form_values['area']), 'farm_areas', FALSE);

    // If the area couldn't be loaded, bail with an error.
    if (empty($area)) {
      drupal_set_message(t('The selected area could not be found.'), 'error');
    }

    // Otherwise, build the list of experiments.
    else {

      // Load group assets that are present in the selected area.
      $assets = farm_movement_area_assets($area);
      $groups = array();
      foreach ($assets as $id => $asset) {
        if ($asset->type == 'group') {
          $groups[$id] = $asset;
        }
      }

      // Build a list of group select list options.
      $experiment_options = array();
      foreach ($groups as $id => $group) {
        $group_label = entity_label('farm_asset', $group);
        $group_url = entity_uri('farm_asset', $group);
        $experiment_options[$id] = l($group_label, $group_url['path']);
      }

      // Experiments selection (group assets).
      $form['fertiliser']['experiments']['experiments'] = array(
        '#type' => 'checkboxes',
        '#title' => t('Experiments'),
        '#options' => $experiment_options,
        '#default_value' => array_keys($experiment_options),
        '#ajax' => array(
          'callback' => 'farm_rothamsted_fertiliser_quick_form_plantings_ajax',
          'wrapper' => 'plantings',
        ),
      );

      // Create a container for plantings.
      $form['fertiliser']['experiments']['plantings'] = array(
        '#type' => 'container',
        '#prefix' => '<div id="plantings">',
        '#suffix' => '</div>',
      );

      // If the user selected experiments, or if default experiments were selected,
      // load them.
      $selected_experiments = array();
      $experiments = array();
      if (!empty($form_values['experiments']['experiments'])) {
        $selected_experiments = $form_values['experiments']['experiments'];
      } elseif (!empty($form['fertiliser']['experiments']['experiments']['#default_value'])) {
        $selected_experiments = $form['fertiliser']['experiments']['experiments']['#default_value'];
      }

      // Load selected experiments.
      if (!empty($selected_experiments)) {
        foreach ($selected_experiments as $group_id) {
          $group = farm_asset_load($group_id);
          if (!empty($group)) {
            $experiments[$group_id] = $group;
          }
        }
      }

      // If there are experiments, build the list of plantings.
      if (!empty($experiments)) {

        // Load planting assets that are in the selected groups.
        $plantings = array();
        foreach ($experiments as $group_id => $group) {
          $assets = farm_group_members($group);
          foreach ($assets as $id => $asset) {
            if ($asset->type == 'planting') {
              $plantings[$id] = $asset;
            }
          }
        }

        // Build a list of planting select list options.
        $planting_options = array();
        foreach ($plantings as $id => $planting) {
          $planting_label = entity_label('farm_asset', $planting);
          $planting_url = entity_uri('farm_asset', $planting);
          $planting_options[$id] = l($planting_label, $planting_url['path']);
        }

        // Plantings selection.
        $form['fertiliser']['experiments']['plantings']['#type'] = 'checkboxes';
        $form['fertiliser']['experiments']['plantings'] += array(
          '#title' => t('Plantings'),
          '#options' => $planting_options,
          '#default_value' => array_keys($planting_options),
        );
      }
    }
  }

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
  $form['fertiliser']['fertiliser'] = array(
    '#type' => 'select',
    '#title' => t('Fertiliser'),
    '#options' => $fertiliser_options,
    '#required' => TRUE,
  );

  // Fertiliser rate.
  $form['fertiliser']['rate'] = array(
    '#type' => 'textfield',
    '#title' => t('Rate of application (kg/ha)'),
    '#input_group' => TRUE,
    '#field_suffix' => t('kg/ha'),
    '#element_validate' => array('element_validate_number'),
    '#required' => TRUE,
  );

  // Load equipment options.
  $equipment = entity_load('farm_asset', FALSE, array('type' => 'equipment'));
  $equipment_options = array();
  if (!empty($equipment)) {
    foreach ($equipment as $id => $asset) {
      $asset_label = entity_label('farm_asset', $asset);
      $asset_url = entity_uri('farm_asset', $asset);
      $equipment_options[$id] = l($asset_label, $asset_url['path']);
    }
  }

  // Equipment reference.
  if (!empty($equipment_options)) {
    $form['fertiliser']['equipment'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Equipment'),
      '#options' => $equipment_options,
    );
  }

  // Load people options.
  $users = entity_load('user');
  $user_options = array();
  foreach ($users as $uid => $user) {

    // Skip user 0 (anonymous) and user 1 (admin).
    if ($uid <= 1) {
      continue;
    }

    // Skip blocked users.
    if (empty($user->status)) {
      continue;
    }

    // Add them to the options.
    $user_options[$uid] = entity_label('user', $user);
  }

  // User reference.
  if (!empty($user_options)) {
    $form['fertiliser']['users'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Operator'),
      '#options' => $user_options,
    );
  }

  // Application date.
  $date_format = 'Y-m-d';
  $form['fertiliser']['date'] = array(
    '#type' => 'date_select',
    '#title' => t('Date'),
    '#date_format' => $date_format,
    '#date_label_position' => 'within',
    '#date_year_range' => '-3:+10',
    '#default_value' => REQUEST_TIME,
    '#required' => TRUE,
  );

  // Time spent.
  $form['fertiliser']['time'] = array(
    '#type' => 'textfield',
    '#title' => t('Hours spent'),
    '#input_group' => TRUE,
    '#field_suffix' => t('hours'),
    '#element_validate' => array('element_validate_number'),
    '#required' => TRUE,
  );

  // Notes.
  $form['fertiliser']['notes'] = array(
    '#type' => 'text_format',
    '#title' => t('Notes'),
    '#format' => 'farm_format',
  );

  // Submit button.
  $form['fertiliser']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit'),
  );

  // Return the form.
  return $form;
}

/**
 * Fertiliser quick form experiments ajax callback.
 */
function farm_rothamsted_fertiliser_quick_form_experiments_ajax($form, &$form_state) {
  return $form['fertiliser']['experiments'];
}

/**
 * Fertiliser quick form plantings ajax callback.
 */
function farm_rothamsted_fertiliser_quick_form_plantings_ajax($form, &$form_state) {
  return $form['fertiliser']['experiments']['plantings'];
}

/**
 * Fertiliser quick form validate.
 */
function farm_rothamsted_fertiliser_quick_form_validate($form, &$form_state) {

  // Alias $form_state['values']['fertiliser'] for easier use.
  $form_values = array();
  if (!empty($form_state['values']['fertiliser'])) {
    $form_values = &$form_state['values']['fertiliser'];
  }

  // Load the area, and save it to the form state storage. Throw an error if
  // there isn't one.
  $area = farm_term($form_values['area'], 'farm_areas', FALSE);
  if (empty($area)) {
    form_set_error('fertiliser][area', t('The selected area could not be found.'));
  }
  $form_state['storage']['area'] = $area;
}

/**
 * Fertiliser quick form submit.
 */
function farm_rothamsted_fertiliser_quick_form_submit($form, &$form_state) {

  // Alias $form_state['values']['fertiliser'] for easier use.
  $form_values = array();
  if (!empty($form_state['values']['fertiliser'])) {
    $form_values = &$form_state['values']['fertiliser'];
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

  // Get the log entity wrapper.
  $log_wrapper = entity_metadata_wrapper('log', $log);

  // Add the area reference.
  $log_wrapper->field_farm_area[] = $area;

  // Add the material.
  $log_wrapper->field_farm_material[] = $material_term;

  // Set the purpose to "Fertiliser".
  $log_wrapper->field_farm_input_purpose->set(t('Fertiliser'));

  // If plantings were selected, reference them in the "Assets" field.
  if (!empty($form_values['experiments']['plantings'])) {
    $planting_ids = array_values(array_filter($form_values['experiments']['plantings']));
    if (!empty($planting_ids)) {
      $log_wrapper->field_farm_asset->set($planting_ids);
    }
  }

  // If equipment was selected, reference them in the "Equipment Used" field.
  if (!empty($form_values['equipment'])) {
    $equipment_ids = array_values(array_filter($form_values['equipment']));
    if (!empty($equipment_ids)) {
      $log_wrapper->field_farm_equipment->set($equipment_ids);
    }
  }

  // If users were selected, reference them in the "Log Owner" field.
  if (!empty($form_values['users'])) {
    $user_ids = array_values(array_filter($form_values['users']));
    if (!empty($user_ids)) {
      $log_wrapper->field_farm_log_owner->set($user_ids);
    }
  }

  // Add notes.
  if (!empty($form_values['notes']['value'])) {
    $notes = check_plain($form_values['notes']['value']);
    $log_wrapper->field_farm_notes->value->set($notes);
    $log_wrapper->field_farm_notes->format->set($form_values['notes']['format']);
  }

  // Save the log (via its wrapper).
  $log_wrapper->save();

  // Link the log to the quick form.
  if (function_exists('farm_quick_entity_link')) {
    farm_quick_entity_link('farm_rothamsted_fertiliser_quick_form', 'log', $log);
  }
}
