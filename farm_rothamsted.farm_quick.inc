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
    'drilling' => array(
      'label' => t('Drilling'),
      'permission' => 'create farm_seeding log entities',
      'form' => 'farm_rothamsted_drilling_quick_form',
      'file' => 'farm_rothamsted.farm_quick.drilling.inc',
    ),
    'cultivation' => array(
      'label' => t('Cultivation'),
      'permission' => 'create farm_activity log entities',
      'form' => 'farm_rothamsted_cultivation_quick_form',
      'file' => 'farm_rothamsted.farm_quick.cultivation.inc',
    ),
    'spraying' => array(
      'label' => t('Spraying'),
      'permission' => 'create farm_input log entities',
      'form' => 'farm_rothamsted_spraying_quick_form',
      'file' => 'farm_rothamsted.farm_quick.spraying.inc',
    ),
    'harvest' => array(
      'label' => t('Harvest'),
      'permission' => 'create farm_harvest log entities',
      'form' => 'farm_rothamsted_harvest_quick_form',
      'file' => 'farm_rothamsted.farm_quick.harvest.inc',
    ),
  );
}
