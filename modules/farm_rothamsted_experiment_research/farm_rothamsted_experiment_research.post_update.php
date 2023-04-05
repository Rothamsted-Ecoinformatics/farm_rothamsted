<?php

/**
 * @file
 * Update hooks for farm_rothamsted_experiment_research.module.
 */

use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Move rotation fields from experiment to design entity with 2.10.1 release.
 */
function farm_rothamsted_experiment_research_post_update_move_rotation_fields(&$sandbox = NULL) {

  $fields = [];
  $fields['rotation_treatment'] = BaseFieldDefinition::create('boolean')
    ->setLabel(t('Rotation as Treatment'))
    ->setDescription(t('Is the rotation a treatment in this experiment design? Rotations which are part of the treatment structure should be added via the plot attributes.'))
    ->setRevisionable(TRUE)
    ->setDisplayConfigurable('form', TRUE);

  $fields['rotation_name'] = BaseFieldDefinition::create('string')
    ->setLabel(t('Rotation name'))
    ->setDescription(t('The name of the rotation.'))
    ->setRevisionable(TRUE)
    ->setDisplayConfigurable('form', TRUE);

  $fields['rotation_description'] = BaseFieldDefinition::create('text_long')
    ->setLabel(t('Rotation description'))
    ->setDescription(t('A description of the rotation.'))
    ->setRevisionable(TRUE)
    ->setDisplayConfigurable('form', TRUE);

  $fields['rotation_crops'] = BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('Rotation Crops'))
    ->setDescription(t('The crops in the rotation.'))
    ->setRevisionable(TRUE)
    ->setSetting('target_type', 'taxonomy_term')
    ->setSetting('handler', 'default:taxonomy_term')
    ->setSetting('handler_settings', [
      'target_bundles' => [
        'plant_type' => 'plant_type',
      ],
      'sort' => [
        'field' => 'name',
        'direction' => 'asc',
      ],
      'auto_create' => FALSE,
    ]);

  $fields['rotation_phasing'] = BaseFieldDefinition::create('text_long')
    ->setLabel(t('Rotation phasing'))
    ->setDescription(t('The phasing of the rotation. E.g. winter wheat - winter oilseed rape - autumn cover crop - spring beans.'))
    ->setRevisionable(TRUE);

  $fields['rotation_notes'] = BaseFieldDefinition::create('text_long')
    ->setLabel(t('Rotation notes'))
    ->setDescription(t('Any additional notes about the rotation.'))
    ->setRevisionable(TRUE);

  /** @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $update_manager */
  $update_manager = \Drupal::entityDefinitionUpdateManager();

  // First remove fields from rothamsted_experiment entity.
  foreach (array_keys($fields) as $field_id) {
    $definition = $update_manager->getFieldStorageDefinition($field_id, 'rothamsted_experiment');
    $update_manager->uninstallFieldStorageDefinition($definition);
  }

  // Finally, add fields to rothamsted_design entity.
  foreach ($fields as $field_id => $field_definition) {
    \Drupal::entityDefinitionUpdateManager()->installFieldStorageDefinition(
      $field_id,
      'rothamsted_design',
      'farm_rothamsted_experiment_research',
      $field_definition,
    );
  }
}
