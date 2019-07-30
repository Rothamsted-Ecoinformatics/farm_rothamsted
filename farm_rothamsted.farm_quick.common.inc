<?php

/**
 * Rothamstead quick form common functions.
 */

/**
 * Common base form.
 *
 * @param $name
 *   Machine name used throughout the form.
 * @param $title
 *   The form fieldset title.
 * @param $form
 *   The form array passed in by the form builder function.
 * @param $form_state
 *   The form state passed in by the form builder function.
 */
function farm_rothamsted_quick_form_base($name, $title, &$form, &$form_state) {

  // Store the machine name for future use.
  $form['name'] = array(
    '#type' => 'value',
    '#value' => $name,
  );

  // Alias $form_state['values'][$name] for easier use.
  $form_values = array();
  if (!empty($form_state['values'][$name])) {
    $form_values = &$form_state['values'][$name];
  }

  // Wrapper fieldset.
  $form[$name] = array(
    '#type' => 'fieldset',
    '#title' => t($title),
    '#tree' => TRUE,
  );

  // Area selection (autocomplete).
  $form[$name]['area'] = array(
    '#type' => 'textfield',
    '#title' => t( 'Area'),
    '#description' => t('Select the area that this pertains to.'),
    '#autocomplete_path' => 'taxonomy/autocomplete/field_farm_area',
    '#required' => TRUE,
    '#ajax' => array(
      'callback' => 'farm_rothamsted_quick_form_base_experiments_ajax',
      'wrapper' => 'experiments',
    ),
    '#weight' => -50,
  );

  // Create a container for experiments.
  $form[$name]['experiments'] = array(
    '#type' => 'container',
    '#prefix' => '<div id="experiments">',
    '#suffix' => '</div>',
    '#weight' => -40,
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
      $form[$name]['experiments']['experiments'] = array(
        '#type' => 'checkboxes',
        '#title' => t('Experiments'),
        '#options' => $experiment_options,
        '#default_value' => array_keys($experiment_options),
        '#ajax' => array(
          'callback' => 'farm_rothamsted_quick_form_base_plantings_ajax',
          'wrapper' => 'plantings',
        ),
      );

      // Create a container for plantings.
      $form[$name]['experiments']['plantings'] = array(
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
      } elseif (!empty($form[$name]['experiments']['experiments']['#default_value'])) {
        $selected_experiments = $form[$name]['experiments']['experiments']['#default_value'];
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
        $form[$name]['experiments']['plantings']['#type'] = 'checkboxes';
        $form[$name]['experiments']['plantings'] += array(
          '#title' => t('Plantings'),
          '#options' => $planting_options,
          '#default_value' => array_keys($planting_options),
        );
      }
    }
  }

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
    $form[$name]['equipment'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Equipment'),
      '#options' => $equipment_options,
      '#weight' => 10,
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
    $form[$name]['users'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Operator'),
      '#options' => $user_options,
      '#weight' => 20,
    );
  }

  // Application date.
  $date_format = 'Y-m-d';
  $form[$name]['date'] = array(
    '#type' => 'date_select',
    '#title' => t('Date'),
    '#date_format' => $date_format,
    '#date_label_position' => 'within',
    '#date_year_range' => '-3:+10',
    '#default_value' => REQUEST_TIME,
    '#required' => TRUE,
    '#weight' => 30,
  );

  // Time spent.
  $form[$name]['time'] = array(
    '#type' => 'textfield',
    '#title' => t('Hours spent'),
    '#input_group' => TRUE,
    '#field_suffix' => t('hours'),
    '#element_validate' => array('element_validate_number'),
    '#required' => TRUE,
    '#weight' => 40,
  );

  // Notes.
  $form[$name]['notes'] = array(
    '#type' => 'text_format',
    '#title' => t('Notes'),
    '#format' => 'farm_format',
    '#weight' => 50,
  );

  // Submit button.
  $form[$name]['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit'),
    '#weight' => 100,
  );

  // Add base validation function.
  $form['#validate'][] = 'farm_rothamsted_quick_form_base_validate';
}

/**
 * Base form experiments ajax callback.
 */
function farm_rothamsted_quick_form_base_experiments_ajax($form, &$form_state) {
  $name = $form_state['values']['name'];
  return $form[$name]['experiments'];
}

/**
 * Base form plantings ajax callback.
 */
function farm_rothamsted_quick_form_base_plantings_ajax($form, &$form_state) {
  $name = $form_state['values']['name'];
  return $form[$name]['experiments']['plantings'];
}

/**
 * Base form validate.
 */
function farm_rothamsted_quick_form_base_validate($form, &$form_state) {

  // Get the machine name.
  $name = $form_state['values']['name'];

  // Alias $form_state['values'][$name] for easier use.
  $form_values = array();
  if (!empty($form_state['values'][$name])) {
    $form_values = &$form_state['values'][$name];
  }

  // Load the area, and save it to the form state storage. Throw an error if
  // there isn't one.
  $area = farm_term($form_values['area'], 'farm_areas', FALSE);
  if (empty($area)) {
    form_set_error($name . '][area', t('The selected area could not be found.'));
  }
  $form_state['storage']['area'] = $area;
}

/**
 * Base form submit helper. This is not actually a submit function, and must be
 * called from the actual form submit function.
 *
 * @param $log
 *   The log object created by the submit function.
 *
 * @return \EntityMetadataWrapper
 *   Returns an Entity Metadata Wrapper object for the log. The submit function
 *   should call save() on this object to complete the process (after performing
 *   its own modifications).
 */
function farm_rothamsted_quick_form_base_submit_helper($log, &$form_state) {

  // Get the machine name.
  $name = $form_state['values']['name'];

  // Alias $form_state['values'][$name] for easier use.
  $form_values = array();
  if (!empty($form_state['values'][$name])) {
    $form_values = &$form_state['values'][$name];
  }

  // Get the log entity wrapper.
  $log_wrapper = entity_metadata_wrapper('log', $log);

  // Get the area from form state storage.
  $area = $form_state['storage']['area'];

  // Add the area reference.
  $log_wrapper->field_farm_area[] = $area;

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

  // Link the log to the quick form.
  if (function_exists('farm_quick_entity_link')) {
    farm_quick_entity_link('farm_rothamsted_' . $name . '_quick_form', 'log', $log);
  }

  // Return the metadata wrapper.
  return $log_wrapper;
}
