<?php

/**
 * @file
 * Update hooks for farm_rothamsted_experiment_research.module.
 */

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\farm_rothamsted_experiment_research\Entity\RothamstedProposalInterface;
use Symfony\Component\Yaml\Yaml;

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
    'restriction_physical' => [
      'boolean' => [
        'label' => t('Physical Obstructions'),
        'description' => t('Are there any physical obstructions in the field that will interfere with farm equipment and general management of the experiment?'),
      ],
      'text' => [
        'label' => 'Physical Obstructions',
        'description' => t('Please describe the physical obstructions.'),
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
    'seed_treatment' => [
      'label' => t('Seed treatment as treatment'),
      'description' => t('Are seed treatments a part of the treatment structure?'),
    ],
    'seed_treatments' => [
      'label' => t('Seed treatments'),
      'description' => t('Please specify any requirements relating to seed treatments.'),
    ],
    'variety_treatment' => [
      'label' => t('Variety as treatment'),
      'description' => t('Is variety a part of the treatment structure?'),
    ],
    'variety_notes' => [
      'label' => t('Variety notes'),
      'description' => t('Any other notes about the varieties requested/selected.'),
    ],
    'cultivation_treatment' => [
      'label' => t('Cultivation as treatment'),
      'description' => t('Is cultivation a part of the treatment structure?'),
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
    'planting_date_treatment' => [
      'label' => t('Planting date as treatment'),
      'description' => t('Is planting date a part of the treatment structure?'),
    ],
    'planting_date' => [
      'label' => t('Planting dates'),
      'description' => t('Request specific planting dates.'),
    ],
    'planting_rate_treatment' => [
      'label' => t('Planting rate as treatment'),
      'description' => t('Is planting rate a part of the treatment structure?'),
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
    'fungicide_treatment' => [
      'label' => t('Fungicides as treatment'),
      'description' => t('Are fungicides a part of the treatment structure?'),
    ],
    'fungicide' => [
      'label' => t('Fungicides'),
      'description' => t('Please specify any requirements relating to fungicides and plant pathogen management.'),
    ],
    'herbicide_treatment' => [
      'label' => t('Herbicides as treatment'),
      'description' => t('Are herbicides a part of the treatment structure?'),
    ],
    'herbicide' => [
      'label' => t('Herbicides'),
      'description' => t('Please specify any requirements relating to herbicides and weed management.'),
    ],
    'insecticide_treatment' => [
      'label' => t('Insecticides as treatment'),
      'description' => t('Are insecticides a part of the treatment structure?'),
    ],
    'insecticide' => [
      'label' => t('Insecticides'),
      'description' => t('Please specify any requirements relating to insecticides and pest management.'),
    ],
    'nematicide_treatment' => [
      'label' => t('Nematicides as treatment'),
      'description' => t('Are nematicides a part of the treatment structure?'),
    ],
    'nematicide' => [
      'label' => t('Nematicides'),
      'description' => t('Please specify any requirements relating to nematodes and nematicides.'),
    ],
    'molluscicide_treatment' => [
      'label' => t('Molluscicides as treatment'),
      'description' => t('Are molluscicides a part of the treatment structure?'),
    ],
    'molluscicide' => [
      'label' => t('Molluscicides'),
      'description' => t('Please specify any requirements relating to slugs, snails and molluscicide management.'),
    ],
    'pgr_treatment' => [
      'label' => t('Plant growth regulators (PGR) as treatment'),
      'description' => t('Are plant growth regulators a part of the treatment structure?'),
    ],
    'pgr' => [
      'label' => t('Plant growth regulators (PGR)'),
      'description' => t('Please specify any requirements relating to lodging and plant growth regulators.'),
    ],
    'irrigation_treatment' => [
      'label' => t('Irrigation as treatment'),
      'description' => t('Is irrigation a part of the treatment structure?'),
    ],
    'irrigation' => [
      'label' => t('Irrigation'),
      'description' => t('Please specify any requirements relating to irrigation.'),
    ],
    'nitrogen_treatment' => [
      'label' => t('Nitrogen (N) as treatment'),
      'description' => t('Is nitrogen a part of the treatment structure?'),
    ],
    'nitrogen' => [
      'label' => t('Nitrogen (N)'),
      'description' => t('Please specify any nitrogen management requests.'),
    ],
    'potassium_treatment' => [
      'label' => t('Potassium (K) as treatment'),
      'description' => t('Is potassium a part of the treatment structure?'),
    ],
    'potassium' => [
      'label' => t('Potassium (K)'),
      'description' => t('Please specify any potassium management requests.'),
    ],
    'phosphorous_treatment' => [
      'label' => t('Phosphorous (P) as treatment'),
      'description' => t('Is phosphorous a part of the treatment structure?'),
    ],
    'phosphorous' => [
      'label' => t('Phosphorous (P)'),
      'description' => t('Please specify any phosphorous management requests.'),
    ],
    'magnesium_treatment' => [
      'label' => t('Magnesium (Mg) as treatment'),
      'description' => t('Is magnesium a part of the treatment structure?'),
    ],
    'magnesium' => [
      'label' => t('Magnesium (Mg)'),
      'description' => t('Please specify any magnesium management requests.'),
    ],
    'sulphur_treatment' => [
      'label' => t('Sulphur (S) as treatment'),
      'description' => t('Is sulphur a part of the treatment structure?'),
    ],
    'sulphur' => [
      'label' => t('Sulphur (S)'),
      'description' => t('Please specify any sulphur management requests.'),
    ],
    'micronutrients_treatment' => [
      'label' => t('Micronutrients as treatment'),
      'description' => t('Are micronutrients a part of the treatment structure?'),
    ],
    'micronutrients' => [
      'label' => t('Micronutrients'),
      'description' => t('Please specify any micronutrient management requests.'),
    ],
    'ph_treatment' => [
      'label' => t('Liming (pH) as treatment'),
      'description' => t('Is liming or pH a part of the treatment structure?'),
    ],
    'ph' => [
      'label' => t('Liming (pH)'),
      'description' => t('Please specify any pH management requests.'),
    ],
    'grain_harvest' => [
      'label' => t('Grain harvest'),
      'description' => t('Please specify any grain harvest management.'),
    ],
    'straw_harvest' => [
      'label' => t('Straw harvest'),
      'description' => t('Please specify any straw harvest management.'),
    ],
    'other_harvest' => [
      'label' => t('Other harvest'),
      'description' => t('Please specify any other harvest management.'),
    ],
    'post_harvest' => [
      'label' => t('Post-harvest management'),
      'description' => t('Please specify any requirements for post-harvest management.'),
    ],
    'post_harvest_interval' => [
      'label' => t('Post-harvest interval'),
      'description' => t('Please specify a post-harvest interval if needed.'),
    ],
    'other_treatment' => [
      'label' => t('Other aspects of management as treatments'),
      'description' => t('Are there any other aspects of management which form part of the treatment structure?'),
    ],
    'other' => [
      'label' => t('Other'),
      'description' => t('Any other issues relating to the experiment management.'),
    ],
  ];
  foreach ($management_fields as $management_field_id => $management_field_info) {
    // Create boolean treatment field or text_long field.
    $field_id = "mgmt_$management_field_id";
    if (str_ends_with($management_field_id, 'treatment')) {
      $fields[$field_id] = BaseFieldDefinition::create('boolean')
        ->setLabel($management_field_info['label'])
        ->setDescription($management_field_info['description'])
        ->setRevisionable(TRUE)
        ->setRequired(TRUE)
        ->setSettings([
          'on_label' => t('Yes'),
          'off_label' => t('No'),
        ])
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayOptions('form', [
          'type' => 'options_buttons',
        ])
        ->setDisplayConfigurable('view', TRUE)
        ->setDisplayOptions('view', [
          'type' => 'boolean',
          'label' => 'inline',
          'settings' => [
            'format' => 'yes-no',
          ],
        ]);
    }
    else {
      $fields[$field_id] = BaseFieldDefinition::create('text_long')
        ->setLabel($management_field_info['label'])
        ->setDescription($management_field_info['description'])
        ->setRevisionable(TRUE)
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayOptions('form', [
          'type' => 'text_textarea',
        ])
        ->setDisplayConfigurable('view', TRUE)
        ->setDisplayOptions('view', [
          'type' => 'text_default',
          'label' => 'inline',
        ]);
    }
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

/**
 * Change rothamsted_experiment code to allow multiple values.
 */
function farm_rothamsted_experiment_research_post_update_2_11_experiment_code(&$sandbox = NULL) {

  /** @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $update_manager */
  $update_manager = \Drupal::entityDefinitionUpdateManager();

  // Remove existing code field.
  $old_definition = $update_manager->getFieldStorageDefinition('code', 'rothamsted_experiment');
  $update_manager->uninstallFieldStorageDefinition($old_definition);
  field_purge_batch(100);

  // Create new definition.
  $new_definition = BaseFieldDefinition::create('string')
    ->setLabel(t('Experiment code'))
    ->setDescription(t('The experiment code.'))
    ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
    ->setRevisionable(TRUE);
  $update_manager->installFieldStorageDefinition('code', 'rothamsted_experiment', 'farm_rothamsted_experiment_research', $new_definition);
}

/**
 * Enable comments for proposal entity.
 */
function farm_rothamsted_experiment_research_post_update_2_11_proposal_comment(&$sandbox = NULL) {

  // First enable comment module.
  if (!\Drupal::service('module_handler')->moduleExists('comment')) {
    \Drupal::service('module_installer')->install(['comment']);
  }

  // Create rothamsted_proposal comment type.
  $config_type_id = 'rothamsted_proposal';
  $config_path = \Drupal::service('extension.list.module')->getPath('farm_rothamsted_experiment_research') . '/config/install';
  $configs = [
    "comment.type.$config_type_id",
    "field.field.comment.$config_type_id.comment_body",
    "core.entity_form_display.comment.$config_type_id.default",
    "core.entity_view_display.comment.$config_type_id.default",
  ];
  foreach ($configs as $config) {
    $data = Yaml::parseFile("$config_path/$config.yml");
    \Drupal::configFactory()->getEditable($config)->setData($data)->save(TRUE);
  }

  // Create new comment base field definition.
  /** @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $update_manager */
  $update_manager = \Drupal::entityDefinitionUpdateManager();
  $new_definition = farm_rothamsted_experiment_research_comment_base_field_definition('rothamsted_proposal');
  $update_manager->installFieldStorageDefinition('comment', 'rothamsted_proposal', 'farm_rothamsted_experiment_research', $new_definition);
}

/**
 * Rothamsted proposal field changes.
 */
function farm_rothamsted_experiment_research_post_update_2_12_proposal_fields(&$sandbox = NULL) {

  // Remove existing proposed amendments field.
  /** @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $update_manager */
  $update_manager = \Drupal::entityDefinitionUpdateManager();
  $old_definition = $update_manager->getFieldStorageDefinition('amendments', 'rothamsted_proposal');
  $update_manager->uninstallFieldStorageDefinition($old_definition);

  // Add unsuitable location field.
  $fields['unsuitable_location'] = BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('Unsuitable Field Location'))
    ->setDescription(t('Please select any field locations which are not suitable for this proposal'))
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

  // Finally, install field storage definitions.
  foreach ($fields as $field_id => $field_definition) {
    $update_manager->installFieldStorageDefinition(
      $field_id,
      'rothamsted_proposal',
      'farm_rothamsted_experiment_research',
      $field_definition,
    );
  }
}

/**
 * Rothamsted design field changes.
 */
function farm_rothamsted_experiment_research_post_update_2_12_design_fields(&$sandbox = NULL) {

  // Add previous cropping field.
  $fields['previous_cropping'] = BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('Previous Cropping'))
    ->setDescription(t('The crops which were grown in the same location immediately before the experiment.'))
    ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
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
