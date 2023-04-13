<?php

/**
 * @file
 * Update hooks for farm_rothamsted_experiment_research.module.
 */

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\farm_rothamsted_experiment_research\Entity\RothamstedProposalInterface;

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

/**
 * Add fields to proposal entity.
 */
function farm_rothamsted_experiment_research_post_update_2_11_proposal_fields_6(&$sandbox = NULL) {

  $fields = [];

  // Physical restriction fields.
  $fields['restriction_physical'] = BaseFieldDefinition::create('boolean')
    ->setLabel('Physical Obstructions')
    ->setRevisionable(TRUE);
  $fields['restriction_physical_desc'] = BaseFieldDefinition::create('text_long')
    ->setLabel('Physical Obstructions')
    ->setRevisionable(TRUE);

  // Requested location.
  $fields['requested_location'] = BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('Requested Location'))
    ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
    ->setSetting('target_type', 'asset')
    ->setSetting('handler', 'views')
    ->setSetting('handler_settings', [
      'view' => [
        'view_name' => 'farm_location_reference',
        'display_name' => 'entity_reference',
        'arguments' => [],
      ],
    ]);

  // Initial quote.
  $fields['initial_quote'] = BaseFieldDefinition::create('file')
    ->setLabel(t('Initial Quote'))
    ->setRevisionable(TRUE)
    ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

  // Finally, add fields to rothamsted_proposal entity.
  foreach ($fields as $field_id => $field_definition) {
    \Drupal::entityDefinitionUpdateManager()->installFieldStorageDefinition(
      $field_id,
      'rothamsted_proposal',
      'farm_rothamsted_experiment_research',
      $field_definition,
    );
  }
}

/**
 * Change statistical design field to text_long.
 */
function farm_rothamsted_experiment_research_post_update_2_11_statistical_design(&$sandbox = NULL) {

  // First collect existing data.
  $proposal_storage = \Drupal::entityTypeManager()->getStorage('rothamsted_proposal');
  $proposals = $proposal_storage->loadMultiple();
  $existing_data = array_map(function (RothamstedProposalInterface $proposal) {
    return $proposal->get('statistical_design')->value;
  }, $proposals);

  /** @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $update_manager */
  $update_manager = \Drupal::entityDefinitionUpdateManager();

  // Remove existing proposal statistical design field.
  $old_definition = $update_manager->getFieldStorageDefinition('statistical_design', 'rothamsted_proposal');
  $update_manager->uninstallFieldStorageDefinition($old_definition);
  field_purge_batch(100);

  // Create new definition.
  $new_definition = BaseFieldDefinition::create('text_long')
    ->setLabel(t('Statistical Design'))
    ->setRevisionable(TRUE)
    ->setRequired(TRUE)
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayOptions('form', [
      'type' => 'string_textarea',
    ])
    ->setDisplayConfigurable('view', TRUE)
    ->setDisplayOptions('view', [
      'type' => 'string',
      'label' => 'inline',
    ]);
  $update_manager->installFieldStorageDefinition('statistical_design', 'rothamsted_proposal', 'farm_rothamsted_experiment_research', $new_definition);

  // Add back existing data.
  foreach ($existing_data as $proposal_id => $design) {
    $proposal = $proposal_storage->load($proposal_id);
    $proposal->set('statistical_design', ['value' => $design, 'format' => 'default']);
    $proposal->save();
  }
}
