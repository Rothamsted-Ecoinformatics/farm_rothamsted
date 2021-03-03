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
 * @param $equipment_group_names
 *   Array: Array of group asset names to load equipment assets from.
 *   Defaults to an empty array that will not display equipment assets.
 * @param $select_plantings
 *   Boolean: whether or not to display a list of plantings associated with
 *   experiments and link created logs to them. Defaults to TRUE.
 */
function farm_rothamsted_quick_form_base($name, $title, &$form, &$form_state, $equipment_group_names = [], $select_plantings = TRUE) {

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
    '#description' => t('Select the area that this pertains to. This will present you with a list of experiments in the area (and in child areas), which you can select all or some of.'),
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

  // If an area is selected, load experiments (active group assets) in it, and
  // in child areas.
  if (!empty($form_values['area'])) {

    // Start an empty array of areas (parent and children).
    $areas = array();

    // Load the parent area.
    $parent_area = farm_term(check_plain($form_values['area']), 'farm_areas', FALSE);

    // If the area couldn't be loaded, bail with an error.
    if (empty($parent_area)) {
      drupal_set_message(t('The selected area could not be found.'), 'error');
    }

    // Otherwise, build the list of experiments.
    else {

      // Start an empty array of experiment options.
      $experiment_options = array();

      // Add the parent area to the list of areas.
      $areas[] = $parent_area;

      // Load all child areas.
      $child_areas = taxonomy_get_children($parent_area->tid);
      $areas = array_merge($areas, $child_areas);

      // Iterate through all areas, load group assets that are present, and add
      // them to the options list.
      foreach ($areas as $area) {
        $assets = farm_movement_area_assets($area);
        foreach ($assets as $id => $asset) {
          if ($asset->type != 'group') {
            continue;
          }
          $group_label = entity_label('farm_asset', $asset);
          $group_url = entity_uri('farm_asset', $asset);
          $experiment_options[$id] = l($group_label, $group_url['path']);
        }
      }

      // Experiments selection (group assets).
      $form[$name]['experiments']['experiments'] = array(
        '#type' => 'checkboxes',
        '#title' => t('Experiments'),
        '#options' => $experiment_options,
        '#default_value' => array_keys($experiment_options),
      );

      // If we want to allow the user to select plantings...
      if ($select_plantings) {

        // Add AJAX to the experiments field.
        $form[$name]['experiments']['experiments']['#ajax'] = array(
          'callback' => 'farm_rothamsted_quick_form_base_plantings_ajax',
          'wrapper' => 'plantings',
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
  }

  // Load specified equipment groups.
  $groups = entity_load('farm_asset', FALSE, array('type' => 'group', 'name' => $equipment_group_names));

  // Load group members.
  $equipment = array();
  foreach ($groups as $group) {
    $equipment += farm_group_members($group);
  }

  // Create equipment options.
  $equipment_options = array();
  if (!empty($equipment)) {
    foreach ($equipment as $id => $asset) {
      if ($asset->type == 'equipment') {
        $asset_label = entity_label('farm_asset', $asset);
        $asset_url = entity_uri('farm_asset', $asset);
        $equipment_options[$id] = l($asset_label, $asset_url['path']);
      }
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

  // Load the farm operator role.
  $operator_role = user_role_load_by_name('Farm Operator');

  // Load uids of users that have the farm operator role.
  $query = 'SELECT DISTINCT(ur.uid) FROM {users_roles} as ur WHERE ur.rid = ' . $operator_role->rid;
  $result = db_query($query);
  $uids = $result->fetchCol();

  // Load users.
  $users = user_load_multiple($uids);

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
    '#date_year_range' => '-15:+10',
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
 * @param $form_state
 *   The form state passed in from the submit function.
 * @param array $notes
 *   Optionally an array of additional notes to add to the log.
 *
 * @return \EntityMetadataWrapper
 *   Returns an Entity Metadata Wrapper object for the log. The submit function
 *   should call save() on this object to complete the process (after performing
 *   its own modifications).
 */
function farm_rothamsted_quick_form_base_submit_helper($log, &$form_state, $notes = array()) {

  // Get the machine name.
  $name = $form_state['values']['name'];

  // Alias $form_state['values'][$name] for easier use.
  $form_values = array();
  if (!empty($form_state['values'][$name])) {
    $form_values = &$form_state['values'][$name];
  }

  // Get the log entity wrapper.
  $log_wrapper = entity_metadata_wrapper('log', $log);

  // If plantings were selected, reference them in the "Assets" field.
  if (!empty($form_values['experiments']['plantings'])) {
    $planting_ids = array_values(array_filter($form_values['experiments']['plantings']));
    if (!empty($planting_ids)) {
      $log_wrapper->field_farm_asset->set($planting_ids);
    }
  }

  // Load the locations of all selected experiments.
  $areas = array();
  if (!empty($form_values['experiments']['experiments'])) {
    $experiment_ids = array_values(array_filter($form_values['experiments']['experiments']));
    $experiments = entity_load('farm_asset', $experiment_ids);
    foreach ($experiments as $experiment) {
      $locations = farm_movement_asset_location($experiment);
      if (!empty($locations)) {
        $areas = array_merge($areas, $locations);
      }
    }
  }

  // If no areas were found, use the area from form state storage.
  if (empty($areas)) {
    $areas[] = $form_state['storage']['area'];
  }

  // Add the area references.
  // If this is a seeding log, then it needs to be added as a movement field
  // collection. Otherwise it can just be added to field_farm_area.
  if ($log->type == 'farm_seeding') {
    $movement = entity_create('field_collection_item', array('field_name' => 'field_farm_movement'));
    $movement->setHostEntity('log', $log);
    $movement_wrapper = entity_metadata_wrapper('field_collection_item', $movement);
    $movement_wrapper->field_farm_move_to = $areas;
    $movement_wrapper->save();
  }
  else {
    $log_wrapper->field_farm_area = $areas;
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

  // Add time spent.
  $time_spent = array(
    'measure' => 'time',
    'value' => $form_values['time'],
    'units' => 'hours',
  );
  farm_quantity_log_add_measurements($log, array($time_spent));

  // Add notes.
  if (!empty($form_values['notes']['value'])) {
    $notes[] = $form_values['notes']['value'];
  }
  if (!empty($notes)) {
    $notes_text = '';
    foreach ($notes as $note) {
      if (!empty($notes_text)) {
        $notes_text .= "\n\n";
      }
      $notes_text .= check_plain($note);
    }
    $log_wrapper->field_farm_notes->value->set($notes_text);
    $log_wrapper->field_farm_notes->format->set($form_values['notes']['format']);
  }

  // Link the log to the quick form.
  if (function_exists('farm_quick_entity_link')) {
    farm_quick_entity_link('farm_rothamsted_' . $name . '_quick_form', 'log', $log);
  }

  // Return the metadata wrapper.
  return $log_wrapper;
}
