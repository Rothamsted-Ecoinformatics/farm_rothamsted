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

/**
 * Add notes field to rothamsted_program entity.
 */
function farm_rothamsted_experiment_research_post_update_2_11_program_notes(&$sandbox = NULL) {

  /** @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $update_manager */
  $update_manager = \Drupal::entityDefinitionUpdateManager();

  // Create notes field definition.
  $new_definition = BaseFieldDefinition::create('text_long')
    ->setLabel(t('Notes'))
    ->setDescription(t('Notes about the research program.'))
    ->setRevisionable(TRUE);
  $update_manager->installFieldStorageDefinition('notes', 'rothamsted_program', 'farm_rothamsted_experiment_research', $new_definition);
}

/**
 * Add restriction and management fields to rothamsted_design entity.
 */
function farm_rothamsted_experiment_research_post_update_2_11_design_restriction_mgmt(&$sandbox = NULL) {

  $restriction_fields = [
    'restriction_crop' => [
      'boolean' => [
        'label' => t('Crop Management Restrictions'),
        'description' => t('Are there any restrictions that affect how the crop(s) in the experiment will be managed (cultivations, pesticide applications, etc?)'),
      ],
      'text' => [
        'label' => t('Description of Crop Management Restrictions'),
        'description' => t('Please describe the crop management restrictions. Note: All aspects of crop management will need to be discussed in detail with the trials team once the proposal has been approved.'),
      ],
    ],
    'restriction_gm' => [
      'boolean' => [
        'label' => t('Genetically Modified (GM) Material'),
        'description' => t('Is there any GM material being used?'),
      ],
      'text' => [
        'label' => t('Description of GM material'),
        'description' => t('Please describe the GM materials.'),
      ],
    ],
    'restriction_ge' => [
      'boolean' => [
        'label' => t('Genetically Edited (GE) Material'),
        'description' => t('Is there any GE material being used?'),
      ],
      'text' => [
        'label' => t('Description of GE material'),
        'description' => t('Please describe the GE materials.'),
      ],
    ],
    'restriction_off_label' => [
      'boolean' => [
        'label' => t('Off-label Products'),
        'description' => t('Is there a requirement for off-label or uncertified products (e.g. pesticides, growth regulators) to be applied?'),
      ],
      'text' => [
        'label' => t('Description of off-label products'),
        'description' => t('Please describe the off-label products.'),
      ],
    ],
    'restriction_licence_perm' => [
      'boolean' => [
        'label' => t('Licence and Permissions'),
        'description' => t('Do you need a specific licence or other permission?'),
      ],
      'text' => [
        'label' => t('Licence and Permissions'),
        'description' => t('Please describe the licence/permission restrictions.'),
      ],
    ],
  ];

  // Add boolean and text_long field for each restriction.
  foreach ($restriction_fields as $restriction_field_id => $restriction_field_info) {
    $fields[$restriction_field_id] = BaseFieldDefinition::create('boolean')
      ->setLabel($restriction_field_info['boolean']['label'])
      ->setDescription($restriction_field_info['boolean']['description'])
      ->setRevisionable(TRUE);
    $description_field_id = $restriction_field_id . '_desc';
    $fields[$description_field_id] = BaseFieldDefinition::create('text_long')
      ->setLabel($restriction_field_info['text']['label'])
      ->setDescription($restriction_field_info['text']['description'])
      ->setRevisionable(TRUE);
  }

  $fields['restriction_other'] = BaseFieldDefinition::create('text_long')
    ->setLabel(t('Other restrictions'))
    ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
    ->setRevisionable(TRUE);

  $fields['mgmt_seed_provision'] = BaseFieldDefinition::create('list_string')
    ->setLabel(t('Seed Provision'))
    ->setDescription(t('Please state who will provide the seed.'))
    ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
    ->setRevisionable(TRUE);

  $management_fields = [
    'seed_trt' => [
      'label' => t('Seed treatments'),
      'description' => t('Please specify any requirements relating ot seed treatments.'),
    ],
    'variety_notes' => [
      'label' => t('Variety notes'),
      'description' => t('Any other notes about the varieties requested/selected.'),
    ],
    'ploughing' => [
      'label' => t('Ploughing'),
      'description' => t('Detail any management related to ploughing.'),
    ],
    'levelling' => [
      'label' => t('Levelling'),
      'description' => t('Detail any management related to levelling.'),
    ],
    'seed_cultivation' => [
      'label' => t('Seed bed cultivation'),
      'description' => t('Detail any management related to seed bed cultivation.'),
    ],
    'planting_date' => [
      'label' => t('Planting dates'),
      'description' => t('Request specific planting dates.'),
    ],
    'seed_rate' => [
      'label' => t('Seed rate'),
      'description' => t('Request specific seed rates.'),
    ],
    'drilling_rate' => [
      'label' => t('Drilling rate'),
      'description' => t('Request specific drilling rates.'),
    ],
    'plant_estab' => [
      'label' => t('Plant Establishment'),
      'description' => t('Detail any management relating to plant establishment.'),
    ],
    'fungicide' => [
      'label' => t('Fungicides'),
      'description' => t('Please specify any requirements relating to fungicides and plant pathogen management.'),
    ],
    'herbicide' => [
      'label' => t('Herbicides'),
      'description' => t('Please specify any requirements relating to herbicides and weed management.'),
    ],
    'insecticide' => [
      'label' => t('Insecticides'),
      'description' => t('Please specify any requirements relating to insecticides and pest management.'),
    ],
    'nematicide' => [
      'label' => t('Nematicides'),
      'description' => t('Please specify any requirements relating to nematodes and nematicides.'),
    ],
    'molluscicide' => [
      'label' => t('Molluscicides'),
      'description' => t('Please specify any requirements relating to slugs, snails and molluscicide management.'),
    ],
    'pgr' => [
      'label' => t('Plant growth regulators (PGR)'),
      'description' => t('Please specify any requirements relating to lodging and plant growth regulators.'),
    ],
    'irrigation' => [
      'label' => t('Irrigation'),
      'description' => t('Please specify any requirements relating to irrigation.'),
    ],
    'nitrogen' => [
      'label' => t('Nitrogen (N)'),
      'description' => t('Please specify any nitrogen management requests.'),
    ],
    'potassium' => [
      'label' => t('Potassium (P)'),
      'description' => t('Please specify any potassium management requests.'),
    ],
    'phosphorous' => [
      'label' => t('Phosphorous (K)'),
      'description' => t('Please specify any phosphorous management requests.'),
    ],
    'magnesium' => [
      'label' => t('Magnesium (Mg)'),
      'description' => t('Please specify any magnesium management requests.'),
    ],
    'sulphur' => [
      'label' => t('Sulphur (S)'),
      'description' => t('Please specify any sulphur management requests.'),
    ],
    'micronutrients' => [
      'label' => t('Micronutrients'),
      'description' => t('Please specify any micronutrient management requests.'),
    ],
    'ph' => [
      'label' => t('Liming (pH)'),
      'description' => t('Please specify any pH management requests.'),
    ],
    'pre_harvest' => [
      'label' => t('Pre-harvest sampling'),
      'description' => t('Describe any pre-harvest sampling.'),
    ],
    'grain_samples' => [
      'label' => t('Grain samples'),
      'description' => t('Do you require any grain samples?'),
    ],
    'grain_harvest' => [
      'label' => t('Grain harvest instructions'),
      'description' => t('Please specify any grain handling instructions.'),
    ],
    'straw_samples' => [
      'label' => t('Straw samples'),
      'description' => t('Do you require straw samples?'),
    ],
    'straw_harvest' => [
      'label' => t('Straw harvest instructions'),
      'description' => t('Please specify any straw harvest instructions.'),
    ],
    'post_harvest' => [
      'label' => t('Post-harvest management'),
      'description' => t('Please specify any requirements for post-harvest management.'),
    ],
    'post_harvest_interval' => [
      'label' => t('Post-harvest interval'),
      'description' => t('Please specify a post-harvest interval if needed.'),
    ],
    'post_harvest_sampling' => [
      'label' => t('Post-harvest sampling'),
      'description' => t('Please describe any post-harvest sampling.'),
    ],
    'physical_obstructions' => [
      'label' => t('Physical obstructions'),
      'description' => t('Are there any physical obstructions in the field that will interfere with farm equipment and general management of the experiment?'),
    ],
    'other' => [
      'label' => t('Other'),
      'description' => t('Any other issues relating to the experiment management.'),
    ],
  ];
  foreach ($management_fields as $management_field_id => $management_field_info) {
    $fields["mgmt_$management_field_id"] = BaseFieldDefinition::create('text_long')
      ->setLabel($management_field_info['label'])
      ->setDescription($management_field_info['description'])
      ->setRevisionable(TRUE);
  }

  // Finally, install field storage definitions.
  foreach ($fields as $field_id => $field_definition) {
    \Drupal::entityDefinitionUpdateManager()->installFieldStorageDefinition(
      $field_id,
      'rothamsted_design',
      'farm_rothamsted_experiment_research',
      $field_definition,
    );
  }
}
