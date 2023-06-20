<?php

/**
 * @file
 * Update hooks for farm_rothamsted_experiment.module.
 */

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\entity\BundleFieldDefinition;
use Symfony\Component\Yaml\Yaml;

/**
 * Create experiment_boundary land type.
 */
function farm_rothamsted_experiment_post_update_create_experiment_boundary_land_type(&$sandbox = NULL) {
  $land_type = 'experiment_boundary';
  $config_path = \Drupal::service('extension.list.module')->getPath('farm_rothamsted_experiment') . "/config/install/farm_land.land_type.$land_type.yml";
  $data = Yaml::parseFile($config_path);
  \Drupal::configFactory()->getEditable("farm_land.land_type.$land_type")->setData($data)->save(TRUE);
}

/**
 * Create experiment_boundary land type.
 */
function farm_rothamsted_experiment_post_update_create_experiment_link_fields(&$sandbox = NULL) {

  // Install the link module.
  if (!\Drupal::moduleHandler()->moduleExists('link')) {
    \Drupal::service('module_installer')->install(['link']);
  }

  // Experiment file link fields.
  $fields['experiment_plan_link'] = BundleFieldDefinition::create('link')
    ->setLabel(t('Experiment plan'))
    ->setRequired(FALSE)
    ->setRevisionable(TRUE)
    ->setSettings([
      'title' => DRUPAL_DISABLED,
      // @see LinkItemInterface::LINK_EXTERNAL.
      'link_type' => 0x10,
    ])
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);
  $fields['experiment_file_link'] = BundleFieldDefinition::create('link')
    ->setLabel(t('Experiment file'))
    ->setRequired(FALSE)
    ->setRevisionable(TRUE)
    ->setSettings([
      'title' => DRUPAL_DISABLED,
      // @see LinkItemInterface::LINK_EXTERNAL.
      'link_type' => 0x10,
    ])
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);

  // Install each field definition.
  foreach ($fields as $field_name => $field_definition) {
    \Drupal::entityDefinitionUpdateManager()->installFieldStorageDefinition(
      $field_name,
      'plan',
      'farm_rothamsted_experiment',
      $field_definition,
    );
  }

}

/**
 * Uninstall experiment people fields.
 */
function farm_rothamsted_experiment_post_update_uninstall_people_fields(&$sandbox = NULL) {

  // Fields to uninstall.
  $uninstall_definitions = [];

  // Build field definitions for old people and email fields.
  $uninstall_field_info = [
    'principle_investigator' => [
      'type' => 'string',
      'label' => t('Principle Investigator'),
      'description' => t('The lead scientist(s) associated with the experiment.'),
      'multiple' => TRUE,
    ],
    'data_steward' => [
      'type' => 'string',
      'label' => t('Data Steward'),
      'description' => t('The data steward(s) associated with the experiment.'),
      'multiple' => TRUE,
    ],
    'statistician' => [
      'type' => 'string',
      'label' => t('Statistician'),
      'description' => t('The statistician(s) associated with the experiment.'),
    ],
    'primary_contact' => [
      'type' => 'string',
      'label' => t('Primary Contact'),
      'description' => t('The primary contact for this experiment'),
    ],
    'secondary_contact' => [
      'type' => 'string',
      'label' => t('Secondary Contact'),
      'description' => t('The secondary contact for this experiment.'),
    ],
  ];
  foreach ($uninstall_field_info as $field_name => $field_info) {
    $uninstall_definitions[$field_name] = \Drupal::service('farm_field.factory')->bundleFieldDefinition($field_info);
  }

  // Build field definitions for contact email fields.
  $uninstall_definitions['primary_contact_email'] = BundleFieldDefinition::create('email')
    ->setLabel(t('Primary Contact Email'))
    ->setDescription(t('The e-mail address of the primary contact.'))
    ->setRevisionable(TRUE)
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);
  $uninstall_definitions['secondary_contact_email'] = BundleFieldDefinition::create('email')
    ->setLabel(t('Secondary Contact Email'))
    ->setDescription(t('The e-mail address of the secondary contact.'))
    ->setRevisionable(TRUE)
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);

  // Uninstall each definition.
  foreach ($uninstall_definitions as $field_name => $field_definition) {
    // Set the field name, entity type and bundle to complete the field
    // storage definition.
    $field_definition
      ->setName($field_name)
      ->setTargetEntityTypeId('plan')
      ->setTargetBundle('rothamsted_experiment');
    \Drupal::entityDefinitionUpdateManager()->uninstallFieldStorageDefinition($field_definition);
  }
}

/**
 * Create experiment contact field.
 */
function farm_rothamsted_experiment_post_update_create_contact_field(&$sandbox = NULL) {

  // User reference.
  $field_info = [
    'type' => 'entity_reference',
    'label' => t('Contacts'),
    'target_type' => 'user',
    'multiple' => TRUE,
  ];
  $field_definition = \Drupal::service('farm_field.factory')->bundleFieldDefinition($field_info);
  \Drupal::entityDefinitionUpdateManager()->installFieldStorageDefinition(
    'contact',
    'plan',
    'farm_rothamsted_experiment',
    $field_definition,
  );
}

/**
 * Change the location field to reference location assets.
 */
function farm_rothamsted_experiment_post_update_location_field_reference_asset(&$sandbox = NULL) {

  // Build and uninstall the old field definition.
  $field_definition = \Drupal::service('farm_field.factory')->bundleFieldDefinition([
    'type' => 'string',
    'label' => t('Field Location(s)'),
    'multiple' => TRUE,
  ]);
  $field_definition
    ->setName('location')
    ->setTargetEntityTypeId('plan')
    ->setTargetBundle('rothamsted_experiment');
  \Drupal::entityDefinitionUpdateManager()->uninstallFieldStorageDefinition($field_definition);

  // Build and install the new field definition.
  $field_definition = \Drupal::service('farm_field.factory')->bundleFieldDefinition([
    'type' => 'entity_reference',
    'label' => t('Field Location(s)'),
    'target_type' => 'asset',
    'target_bundle' => 'land',
    'multiple' => TRUE,
  ]);
  \Drupal::entityDefinitionUpdateManager()->installFieldStorageDefinition(
    'location',
    'plan',
    'farm_rothamsted_experiment',
    $field_definition,
  );
}

/**
 * Update plot assets to not be locations.
 */
function farm_rothamsted_experiment_post_update_update_plot_location(&$sandbox = NULL) {

  // Update asset_field_data.
  \Drupal::database()->update('asset_field_data')
    ->fields([
      'is_location' => 0,
    ])
    ->condition('type', 'plot')
    ->execute();

  // Subselect plot asset IDs for updating asset_field_revision.
  $plot_ids = \Drupal::database()->select('asset_field_data', 'afd')
    ->fields('afd', ['id'])
    ->condition('type', 'plot');

  // Update asset_field_revision.
  \Drupal::database()->update('asset_field_revision')
    ->fields([
      'is_location' => 0,
    ])
    ->condition('id', $plot_ids, 'IN')
    ->execute();

  // Invalidate cache for plot assets.
  /** @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator */
  $cache_tags_invalidator = Drupal::service('cache_tags.invalidator');
  $cache_tags_invalidator->invalidateTags(['asset_list', 'asset_list:plot']);
}

/**
 * Create rothamsted_sponsor and rothamsted_experiment_admin roles.
 */
function farm_rothamsted_experiment_post_update_create_sponsor_experiment_admin_roles(&$sandbox = NULL) {
  foreach (['rothamsted_experiment_admin', 'rothamsted_sponsor'] as $role_id) {
    $config_path = \Drupal::service('extension.list.module')->getPath('farm_rothamsted_experiment') . "/config/install/user.role.$role_id.yml";
    $data = Yaml::parseFile($config_path);
    \Drupal::configFactory()->getEditable("user.role.$role_id")->setData($data)->save(TRUE);
  }
}

/**
 * Enable datetime module.
 */
function farm_rothamsted_experiment_post_update_2_10_1_enable_datetime(&$sandbox = NULL) {
  if (!\Drupal::service('module_handler')->moduleExists('datetime')) {
    \Drupal::service('module_installer')->install(['datetime']);
  }
}

/**
 * Add experiment plan fields added with 2.10 release.
 */
function farm_rothamsted_experiment_post_update_2_10_2_add_plan_fields(&$sandbox = NULL) {

  // Additional fields added with 2.10.
  $fields = [];
  $fields['status_notes'] = BundleFieldDefinition::create('text_long')
    ->setLabel(t('Status notes'))
    ->setDescription(t('Any notes about the design status.'))
    ->setRevisionable(TRUE);
  $fields['study_description'] = BundleFieldDefinition::create('text_long')
    ->setLabel(t('Description'))
    ->setDescription(t('A description of the study period.'))
    ->setRevisionable(TRUE);
  $fields['study_number'] = BundleFieldDefinition::create('integer')
    ->setLabel(t('Study number'))
    ->setDescription(t('A consecutive number that can be used to identify the study.'))
    ->setRevisionable(TRUE)
    ->setSetting('min', 0);
  $fields['start'] = BundleFieldDefinition::create('datetime')
    ->setLabel(t('Start date'))
    ->setDescription(t('The start date of the program.'))
    ->setRevisionable(TRUE)
    ->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATE);
  $fields['end'] = BundleFieldDefinition::create('datetime')
    ->setLabel(t('End date'))
    ->setDescription(t('The end date of the program.'))
    ->setRevisionable(TRUE)
    ->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATE);
  $fields['current_phase'] = BundleFieldDefinition::create('string')
    ->setLabel(t('Current Phase'))
    ->setDescription(t('The current phase that the rotation is in.'))
    ->setRevisionable(TRUE);
  $fields['cost_code_allocation'] = BundleFieldDefinition::create('text_long')
    ->setLabel(t('Cost code allocation'))
    ->setDescription(t('List the cost codes and percentage allocations.'))
    ->setRevisionable(TRUE);
  $fields['amendments'] = BundleFieldDefinition::create('text_long')
    ->setLabel(t('Amendments'))
    ->setDescription(t('A description of any changes made to the experiment.'))
    ->setRevisionable(TRUE);

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
    $fields[$restriction_field_id] = BundleFieldDefinition::create('boolean')
      ->setLabel($restriction_field_info['boolean']['label'])
      ->setDescription($restriction_field_info['boolean']['description'])
      ->setRevisionable(TRUE);
    $description_field_id = $restriction_field_id . '_desc';
    $fields[$description_field_id] = BundleFieldDefinition::create('text_long')
      ->setLabel($restriction_field_info['text']['label'])
      ->setDescription($restriction_field_info['text']['description'])
      ->setRevisionable(TRUE);
  }

  $fields['restriction_other'] = BundleFieldDefinition::create('text_long')
    ->setLabel(t('Other restrictions'))
    ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
    ->setRevisionable(TRUE);

  $fields['mgmt_seed_provision'] = BundleFieldDefinition::create('list_string')
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
      'label' => t('Potassium (K)'),
      'description' => t('Please specify any potassium management requests.'),
    ],
    'phosphorous' => [
      'label' => t('Phosphorous (P)'),
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
    $fields["mgmt_$management_field_id"] = BundleFieldDefinition::create('text_long')
      ->setLabel($management_field_info['label'])
      ->setDescription($management_field_info['description'])
      ->setRevisionable(TRUE);
  }

  // Finally, install field storage definitions.
  foreach ($fields as $field_id => $field_definition) {
    \Drupal::entityDefinitionUpdateManager()->installFieldStorageDefinition(
      $field_id,
      'plan',
      'farm_rothamsted_experiment',
      $field_definition,
    );
  }
}

/**
 * Add fields to experiment plan.
 */
function farm_rothamsted_experiment_post_update_2_11_experiment_fields(&$sandbox = NULL) {

  // Agreed quote.
  $fields = [];
  $fields['agreed_quote'] = BundleFieldDefinition::create('file')
    ->setLabel(t('Agreed Quote'))
    ->setRevisionable(TRUE)
    ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

  // Finally, add fields to rothamsted_experiment plan.
  foreach ($fields as $field_id => $field_definition) {
    \Drupal::entityDefinitionUpdateManager()->installFieldStorageDefinition(
      $field_id,
      'plan',
      'farm_rothamsted_experiment',
      $field_definition,
    );
  }
}

/**
 * Remove restriction and management fields from experiment plans.
 */
function farm_rothamsted_experiment_post_update_2_11_remove_restriction_mgmt(&$sandbox = NULL) {

  /** @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $update_manager */
  $update_manager = \Drupal::entityDefinitionUpdateManager();

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

    // Uninstall boolean field.
    $boolean_field = $update_manager->getFieldStorageDefinition($restriction_field_id, 'plan');
    $update_manager->uninstallFieldStorageDefinition($boolean_field);

    // Uninstall description field.
    $description_field_id = $restriction_field_id . '_desc';
    $description_field = $update_manager->getFieldStorageDefinition($description_field_id, 'plan');
    $update_manager->uninstallFieldStorageDefinition($description_field);
  }

  // Uninstall restriction other.
  $other_field = $update_manager->getFieldStorageDefinition('restriction_other', 'plan');
  $update_manager->uninstallFieldStorageDefinition($other_field);

  // Uninstall mgmt_seed_provision.
  $mgmt_seed_field = $update_manager->getFieldStorageDefinition('mgmt_seed_provision', 'plan');
  $update_manager->uninstallFieldStorageDefinition($mgmt_seed_field);

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
      'label' => t('Potassium (K)'),
      'description' => t('Please specify any potassium management requests.'),
    ],
    'phosphorous' => [
      'label' => t('Phosphorous (P)'),
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
    $mgmt_field = $update_manager->getFieldStorageDefinition("mgmt_$management_field_id", 'plan');
    $update_manager->uninstallFieldStorageDefinition($mgmt_field);
  }
}

/**
 * Cleanup fields from experiment plans that are now on research entities.
 */
function farm_rothamsted_experiment_post_update_2_11_cleanup_experiment_plan_fields(&$sandbox = NULL) {
  /** @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $update_manager */
  $update_manager = \Drupal::entityDefinitionUpdateManager();
  $fields = [
    'project_code',
    'start',
    'end',
    'rres_experiment_category',
    'objective',
    'amendments',
  ];
  foreach ($fields as $field_id) {
    $other_field = $update_manager->getFieldStorageDefinition($field_id, 'plan');
    $update_manager->uninstallFieldStorageDefinition($other_field);
  }
}

/**
 * Create other_links field.
 */
function farm_rothamsted_experiment_post_update_2_13_add_generic_link_field(&$sandbox = NULL) {

  // Experiment file link fields.
  $fields['other_links'] = BundleFieldDefinition::create('link')
    ->setLabel(t('Other links'))
    ->setRequired(FALSE)
    ->setRevisionable(TRUE)
    ->setSettings([
      'title' => DRUPAL_OPTIONAL,
      // @see LinkItemInterface::LINK_EXTERNAL.
      'link_type' => 0x10,
    ])
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);

  // Install each field definition.
  foreach ($fields as $field_name => $field_definition) {
    \Drupal::entityDefinitionUpdateManager()->installFieldStorageDefinition(
      $field_name,
      'plan',
      'farm_rothamsted_experiment',
      $field_definition,
    );
  }
}

/**
 * Migrate experiment code to study period field.
 */
function farm_rothamsted_experiment_post_update_2_13_migrate_experiment_code_study_period(&$sandbox) {

  $plan_storage = \Drupal::entityTypeManager()->getStorage('plan');

  // This function will be run as a batch operation. On the first run, we will
  // make preparations. This logic should only run once.
  if (!isset($sandbox['current_id'])) {

    // Query the database for all experiment plans.
    $sandbox['plan_ids'] = array_values($plan_storage->getQuery()
      ->condition('type', 'rothamsted_experiment')
      ->execute());

    // Install the new total_price field.
    $update_manager = \Drupal::entityDefinitionUpdateManager();
    $options = [
      'type' => 'string',
      'label' => t('Study Period ID'),
    ];
    $field_definition = \Drupal::service('farm_field.factory')->bundleFieldDefinition($options);
    $update_manager->installFieldStorageDefinition('study_period_id', 'plan', 'farm_rothamsted_experiment', $field_definition);

    // Track progress.
    $sandbox['current_id'] = 0;
    $sandbox['#finished'] = 0;
  }

  // Iterate through plans, 10 at a time.
  $quantity_count = count($sandbox['plan_ids']);
  $end_quantity = $sandbox['current_id'] + 10;
  $end_quantity = $end_quantity > $quantity_count ? $quantity_count : $end_quantity;
  for ($i = $sandbox['current_id']; $i < $end_quantity; $i++) {

    // Iterate the global counter.
    $sandbox['current_id']++;

    // Get the quantity ID.
    if (isset($sandbox['plan_ids'][$i]) && $plan = $plan_storage->load($sandbox['plan_ids'][$i])) {
      $plan->set('study_period_id', $plan->get('experiment_code')->value);
      $plan->save();
    }
  }

  // Update progress.
  if (!empty($sandbox['plan_ids'])) {
    $sandbox['#finished'] = $sandbox['current_id'] / count($sandbox['plan_ids']);
  }
  else {
    $sandbox['#finished'] = 1;
  }

  return NULL;
}

/**
 * Uninstall contact field.
 */
function farm_rothamsted_experiment_post_update_2_13_uninstall_contact_field(&$sandbox = NULL) {
  $update_manager = \Drupal::entityDefinitionUpdateManager();
  $contact_field = $update_manager->getFieldStorageDefinition('contact', 'plan');
  \Drupal::entityDefinitionUpdateManager()->uninstallFieldStorageDefinition($contact_field);
}
