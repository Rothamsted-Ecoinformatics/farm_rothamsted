<?php

/**
 * @file
 * Update hooks for farm_rothamsted_experiment_research.module.
 */

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\farm_rothamsted_experiment_research\Entity\RothamstedProposalInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\link\LinkItemInterface;
use Drupal\user\Entity\Role;
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

  $fields['statistician'] = BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('Statistician'))
    ->setDescription(t('Please select the statistician associated with this proposal.'))
    ->setRequired(TRUE)
    ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
    ->setSetting('target_type', 'rothamsted_researcher')
    ->setSetting('handler', 'views')
    ->setSetting('handler_settings', [
      'view' => [
        'view_name' => 'rothamsted_researcher_reference',
        'display_name' => 'entity_reference',
        'arguments' => [
          'role' => 'statistician',
        ],
      ],
    ]);

  $fields['data_steward'] = BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('Data Steward'))
    ->setDescription(t('Please select the data steward associated with this proposal.'))
    ->setRequired(TRUE)
    ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
    ->setSetting('target_type', 'rothamsted_researcher')
    ->setSetting('handler', 'views')
    ->setSetting('handler_settings', [
      'view' => [
        'view_name' => 'rothamsted_researcher_reference',
        'display_name' => 'entity_reference',
        'arguments' => [
          'role' => 'statistician',
        ],
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

/**
 * Add rothamsted design file fields.
 */
function farm_rothamsted_experiment_research_post_update_2_13_1_file_fields(&$sandbox = NULL) {

  // Common file field settings.
  $file_settings = [
    'file_directory' => 'rothamsted/rothamsted_design/[date:custom:Y]-[date:custom:m]',
    'max_filesize' => '',
    'handler' => 'default:file',
    'handler_settings' => [],
  ];
  $file_field_settings = $file_settings + [
    'description_field' => TRUE,
    'file_extensions' => 'csv doc docx gz geojson gpx kml kmz logz mp3 odp ods odt ogg pdf ppt pptx tar tif tiff txt wav xls xlsx zip',
  ];
  $fields['file'] = BaseFieldDefinition::create('file')
    ->setLabel(t('File'))
    ->setDescription(t('Upload files associated with this design.'))
    ->setRevisionable(TRUE)
    ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
    ->setSettings($file_field_settings);

  $image_field_settings = $file_settings + [
    'file_extensions' => 'png gif jpg jpeg',
  ];
  $fields['image'] = BaseFieldDefinition::create('image')
    ->setLabel(t('Image'))
    ->setDescription(t('Upload files associated with this design.'))
    ->setRevisionable(TRUE)
    ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
    ->setSettings($image_field_settings);

  $fields['link'] = BaseFieldDefinition::create('link')
    ->setLabel(t('Links'))
    ->setDescription(t('Links to external website and documents associated with the design.'))
    ->setRevisionable(TRUE)
    ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
    ->setSettings([
      'title' => DRUPAL_OPTIONAL,
      'link_type' => LinkItemInterface::LINK_EXTERNAL,
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

/**
 * Make rotation crop field multivalue.
 */
function farm_rothamsted_experiment_research_post_update_2_13_2_design_crop_field(&$sandbox = NULL) {

  // Init sandbox.
  if (!isset($sandbox['current_id'])) {

    // Query existing data.
    $sandbox['design_data'] = \Drupal::database()->select('rothamsted_design_data', 'rdd')
      ->fields('rdd', ['id', 'rotation_crops'])
      ->condition('rdd.rotation_crops', NULL, 'IS NOT NULL')
      ->execute()
      ->fetchAll();

    // Create the new field definition.
    $new_field = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Rotation Crops'))
      ->setRevisionable(TRUE)
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED);
    \Drupal::entityDefinitionUpdateManager()->installFieldStorageDefinition(
      'rotation_crop',
      'rothamsted_design',
      'farm_rothamsted_experiment_research',
      $new_field,
    );

    // Track progress.
    $sandbox['current_id'] = 0;
    $sandbox['#finished'] = 0;
  }

  // Iterate through designs, 10 at a time.
  $design_storage = \Drupal::entityTypeManager()->getStorage('rothamsted_design');
  $quantity_count = count($sandbox['design_data']);
  $end_id = $sandbox['current_id'] + 10;
  $end_id = $end_id > $quantity_count ? $quantity_count : $end_id;
  for ($i = $sandbox['current_id']; $i < $end_id; $i++) {

    // Iterate the global counter.
    $sandbox['current_id']++;

    // Get the quantity ID.
    if (isset($sandbox['design_data'][$i]->id) && $design = $design_storage->load($sandbox['design_data'][$i]->id)) {
      $design->set('rotation_crop', $sandbox['design_data'][$i]->rotation_crops);
      $design->save();
    }
  }

  // Update progress.
  if (!empty($sandbox['design_data'])) {
    $sandbox['#finished'] = $sandbox['current_id'] / count($sandbox['design_data']);
  }
  else {
    $sandbox['#finished'] = 1;
  }

  // Remove old field definition.
  if ($sandbox['#finished'] == 1) {
    $update_manager = \Drupal::entityDefinitionUpdateManager();
    $old_field = $update_manager->getFieldStorageDefinition('rotation_crops', 'rothamsted_design');
    \Drupal::entityDefinitionUpdateManager()->uninstallFieldStorageDefinition($old_field);
  }

  return NULL;
}

/**
 * Create planting and harvest year fields.
 */
function farm_rothamsted_experiment_research_post_update_2_14_add_year_fields(&$sandbox = NULL) {

  // Integer year fields.
  $fields['planting_year'] = BaseFieldDefinition::create('integer')
    ->setLabel(t('Planting Year'))
    ->setDescription(t('The planting year for the study.'))
    ->setRevisionable(TRUE)
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE)
    ->setSetting('min', 1800)
    ->setSetting('max', 3000);

  $fields['harvest_year'] = BaseFieldDefinition::create('integer')
    ->setLabel(t('Harvest Year'))
    ->setDescription(t('The year the experiment is to be harvested.'))
    ->setRevisionable(TRUE)
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE)
    ->setSetting('min', 1800)
    ->setSetting('max', 3000);

  // Install each field definition.
  foreach ($fields as $field_name => $field_definition) {
    \Drupal::entityDefinitionUpdateManager()->installFieldStorageDefinition(
      $field_name,
      'rothamsted_proposal',
      'farm_rothamsted_experiment_research',
      $field_definition,
    );
  }
}

/**
 * Add drill spacing and organic amendments design fields.
 */
function farm_rothamsted_experiment_research_post_update_2_15_add_design_fields(&$sandbox) {

  // Create drill spacing and organic amendments fields.
  $fields = [];
  $management_fields = [
    'drill_spacing' => [
      'label' => t('Drill spacing'),
      'description' => t('Request specific drill spacing.'),
    ],
    'organic_amendments' => [
      'label' => t('Organic amendments'),
      'description' => t('Request specific organic amendments (farmyard manure, poultry manure, compost, etc).'),
    ],
  ];
  foreach ($management_fields as $management_field_id => $management_field_info) {
    $field_id = "mgmt_$management_field_id";
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
 * Add review field on proposal comments.
 */
function farm_rothamsted_experiment_research_post_update_2_17_proposal_comment_review(&$sandbox = NULL) {

  // Create review field on rothamsted proposal comment.
  $config_path = \Drupal::service('extension.list.module')->getPath('farm_rothamsted_experiment_research') . '/config/install';
  $yml = Yaml::parse(file_get_contents("$config_path/field.storage.comment.proposal_review.yml"));
  if (!FieldStorageConfig::loadByName($yml['entity_type'], $yml['field_name'])) {
    FieldStorageConfig::create($yml)->save();
  }
  $yml = Yaml::parse(file_get_contents("$config_path/field.field.comment.rothamsted_proposal.proposal_review.yml"));
  if (!FieldConfig::loadByName($yml['entity_type'], $yml['bundle'], $yml['field_name'])) {
    FieldConfig::create($yml)->save();
  }
}

/**
 * Migrate design plot fields to notes field.
 */
function farm_rothamsted_experiment_research_post_update_2_17_migrate_design_plot_fields(&$sandbox = NULL) {

  // Init sandbox.
  $design_storage = \Drupal::entityTypeManager()->getStorage('rothamsted_design');
  if (!isset($sandbox['current_id'])) {

    // Query existing data.
    $sandbox['design_ids'] = array_values($design_storage->getQuery()
      ->accessCheck(FALSE)
      ->execute() ?? []);

    // Track progress.
    $sandbox['current_id'] = 0;
    $sandbox['#finished'] = 0;
  }

  // Bail if no designs exist.
  if (empty($sandbox['design_ids'])) {
    $sandbox['#finished'] = 1;
    return NULL;
  }

  $fields_to_remove = [
    'num_blocks',
    'num_plots_block',
    'num_mainplots',
    'num_subplots_mainplots',
    'num_subplots',
    'num_subsubplots_subplot',
    'num_subsubplots',
    'horizontal_row_spacing',
    'vertical_row_spacing',
    'plot_length',
    'plot_width',
    'plot_area',
    'num_rows',
    'num_columns',
  ];

  // Copy all relevant fields to the notes.
  // Keep track if data is migrated so we can update the revision notes.
  $notes = [];
  /** @var \Drupal\farm_rothamsted_experiment_research\Entity\RothamstedDesignInterface $design */
  $current_id = $sandbox['design_ids'][$sandbox['current_id']];
  if ($current_id && $design = $design_storage->load($current_id)) {
    foreach ($fields_to_remove as $field_id) {
      if (!$design->get($field_id)->isEmpty()) {
        /** @var \Drupal\Core\Field\FieldItemInterface $field */
        $field = $design->get($field_id);
        $label = $field->getFieldDefinition()->getLabel();
        $notes[] = "$label: {$field->value}";
      }
    }

    // Save the notes field with a revision message.
    if (count($notes)) {
      $notes_format = $design->get('notes')->format;
      $notes_value = $design->get('notes')->value;
      foreach ($notes as $note_line) {
        $notes_value .= "$note_line \n";
      }
      $design->set('notes', ['value' => $notes_value, 'format' => $notes_format]);
      $design->setNewRevision(TRUE);
      $design->setRevisionLogMessage('Design plot metadata copied to notes field.');
      $design->save();
    }
  }

  // Iterate the global counter.
  $sandbox['current_id']++;

  // Update progress.
  if (!empty($sandbox['design_ids'])) {
    $sandbox['#finished'] = $sandbox['current_id'] / count($sandbox['design_ids']);
  }
  else {
    $sandbox['#finished'] = 1;
  }

  return NULL;
}

/**
 * Remove design as treatment fields.
 */
function farm_rothamsted_experiment_research_post_update_2_17_remove_design_as_treatment_fields(&$sandbox = NULL) {
  $fields_to_remove = [
    'seed_treatment',
    'variety_treatment',
    'cultivation_treatment',
    'planting_date_treatment',
    'planting_rate_treatment',
    'fungicide_treatment',
    'herbicide_treatment',
    'insecticide_treatment',
    'nematicide_treatment',
    'molluscicide_treatment',
    'pgr_treatment',
    'irrigation_treatment',
    'nitrogen_treatment',
    'potassium_treatment',
    'phosphorous_treatment',
    'magnesium_treatment',
    'sulphur_treatment',
    'micronutrients_treatment',
    'ph_treatment',
    'other_treatment',
  ];
  $update_manager = \Drupal::entityDefinitionUpdateManager();
  foreach ($fields_to_remove as $field_id) {
    $old_field = $update_manager->getFieldStorageDefinition("mgmt_$field_id", 'rothamsted_design');
    \Drupal::entityDefinitionUpdateManager()->uninstallFieldStorageDefinition($old_field);
  }
}

/**
 * Add design fields.
 */
function farm_rothamsted_experiment_research_post_update_2_17_add_design_fields(&$sandbox) {

  // Create dependent_variables and unequal_replication fields.
  $fields = [];
  $fields['dependent_variables'] = BaseFieldDefinition::create('text_long')
    ->setLabel(t('Dependant Variables'))
    ->setDescription(t('Describe the dependant variables, adding a new box for each variable. These are also called outcome or response variables, and are the measurement values that are being predicted (or their variation measured) by this experiment.'))
    ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
    ->setRevisionable(TRUE);
  $fields['unequal_replication'] = BaseFieldDefinition::create('boolean')
    ->setLabel(t('Unequal Replication'))
    ->setDescription(t('Please check if the experiment has unequal replication, in which case the replication strategy should be fully described in the Design Description.'))
    ->setRevisionable(TRUE)
    ->setRequired(TRUE)
    ->setDefaultValue(0)
    ->setSettings([
      'on_label' => t('Yes'),
      'off_label' => t('No'),
    ])
    ->setInitialValue(0);

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
 * Update roles.
 */
function farm_rothamsted_experiment_research_post_update_2_17_update_research_roles(&$sandbox) {
  // Delete research viewer.
  if ($research_viewer = Role::load('rothamsted_research_viewer')) {
    $research_viewer->delete();
  }

  // Create new roles.
  foreach (['rothamsted_research_lead', 'rothamsted_research_restricted_viewer', 'rothamsted_research_reviewer'] as $role_id) {
    $config_path = \Drupal::service('extension.list.module')->getPath('farm_rothamsted_experiment_research') . "/config/install/user.role.$role_id.yml";
    $data = Yaml::parseFile($config_path);
    \Drupal::configFactory()->getEditable("user.role.$role_id")->setData($data)->save(TRUE);
  }
}
