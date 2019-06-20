<?php
/**
 * @file
 * Rothamsted quick forms.
 */

/**
 * Implements hook_farm_quick_forms().
 */
function farm_rothamsted_farm_quick_forms() {
  return array(
    'fertiliser' => array(
      'label' => t('Fertiliser'),
      'permission' => 'create farm_input log entities',
      'form' => 'farm_rothamsted_fertiliser_quick_form',
      'file' => 'farm_rothamsted.farm_quick.fertiliser.inc',
    ),
  );
}
