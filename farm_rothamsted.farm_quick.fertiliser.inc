<?php

/**
 * @file
 * Rothamsted fertilising quick form.
 */

/**
 * Fertiliser quick form.
 */
function farm_rothamsted_fertiliser_quick_form($form, &$form_state) {

  // Wrapper fieldset.
  $form['fertiliser'] = array(
    '#type' => 'fieldset',
    '#title' => t('Fertiliser Application Form'),
    '#tree' => TRUE,
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
 * Fertiliser quick form validate.
 */
function farm_rothamsted_fertiliser_quick_form_validate($form, &$form_state) {

}

/**
 * Fertiliser quick form submit.
 */
function farm_rothamsted_fertiliser_quick_form_submit($form, &$form_state) {

}
