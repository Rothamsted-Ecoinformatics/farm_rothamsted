<?php

namespace Drupal\farm_rothamsted_experiment\Plugin\Plan\PlanType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\entity\BundleFieldDefinition;
use Drupal\farm_entity\Plugin\Plan\PlanType\FarmPlanType;
use Drupal\link\LinkItemInterface;

/**
 * Provides the experiment plan type.
 *
 * @PlanType(
 *   id = "rothamsted_experiment",
 *   label = @Translation("Experiment"),
 * )
 */
class Experiment extends FarmPlanType {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    // Remove the plan log field.
    unset($fields['log']);

    // Define RRES Experiment Cateogry options.
    $rres_categories_options = [
      'AN' => $this->t('Annual Experiments'),
      'CS' => $this->t('Crop Sequence Experiments'),
      'CL' => $this->t('Classical Experiments'),
      'LTE' => $this->t('Long Term Experiments'),
      'EC' => $this->t('Energy Crop Experiments'),
      'NA' => $this->t('No Proposition Number'),
    ];

    // Build fields using the FarmFieldFactory as possible.
    $field_info = [
      // Plot reference field.
      'plot' => [
        'type' => 'entity_reference',
        'label' => $this->t('Plots'),
        'description' => $this->t('Plot assets associated with this experiment.'),
        'target_type' => 'asset',
        'target_bundle' => 'plot',
        'multiple' => TRUE,
        'hidden' => TRUE,
      ],
      // General fields.
      'abbreviation' => [
        'type' => 'string',
        'label' => $this->t('Name (Abbreviation)'),
        'description' => $this->t('An abbreviation of the experiment name.'),
      ],
      'experiment_code' => [
        'type' => 'string',
        'label' => $this->t('Experiment Code'),
        'description' => $this->t('The unique identifier for the study, for example 2020/R/CS/790.'),
      ],
      'project_code' => [
        'type' => 'string',
        'label' => $this->t('Project Code'),
        'description' => $this->t('The project code associated with the project.'),
      ],
      'cost_code' => [
        'type' => 'string',
        'label' => $this->t('Cost Code'),
        'description' => $this->t('The cost code associated with the project.'),
        'multiple' => TRUE,
      ],
      'location' => [
        'type' => 'entity_reference',
        'label' => $this->t('Field Location(s)'),
        'description' => $this->t('The field(s) or location(s) of the experiment.'),
        'target_type' => 'asset',
        'target_bundle' => 'land',
        'multiple' => TRUE,
      ],
      // People fields.
      'contact' => [
        'type' => 'entity_reference',
        'label' => $this->t('Contacts'),
        'target_type' => 'user',
        'multiple' => TRUE,
      ],
      // Trial design fields.
      'rres_experiment_category' => [
        'type' => 'list_string',
        'label' => $this->t('RRES Experiment Category'),
        'description' => $this->t('The type of experiment.'),
        'List (select one): Annual Experiments, Crop Sequence Experiments, Classical Experiments. Long Term Experiments, Energy Crop Experiments, No Proposition Number' => 1,
        'allowed_values' => $rres_categories_options,
      ],
      'objective' => [
        'type' => 'text_long',
        'label' => $this->t('Objective'),
        'description' => $this->t('The objective(s) of the experiment.'),
      ],
      'plant_type' => [
        'type' => 'entity_reference',
        'label' => $this->t('Crop(s)'),
        'description' => $this->t('The crop(s) planted in the experiment.'),
        'target_type' => 'taxonomy_term',
        'target_bundle' => 'crop_family',
        'auto_create' => FALSE,
        'multiple' => TRUE,
      ],
      // Plan status fields.
      'status_notes' => [
        'type' => 'text_long',
        'label' => $this->t('Status notes'),
        'description' => $this->t('Any notes about the experiment plan status.'),
      ],
      'deviations' => [
        'type' => 'text_long',
        'label' => $this->t('Deviations from plan'),
        'description' => $this->t('Any deviations from the original statistical design.'),
      ],
      'growing_conditions' => [
        'type' => 'text_long',
        'label' => $this->t('Growing Conditions'),
        'description' => $this->t('A description of the growing conditions, where relevant.'),
      ],
      'reason_for_failure' => [
        'type' => 'text_long',
        'label' => $this->t('Reason for Failure'),
        'description' => $this->t('Notes about the cause of crop failure, where relevant.'),
      ],
    ];
    foreach ($field_info as $name => $info) {
      $fields[$name] = $this->farmFieldFactory->bundleFieldDefinition($info);
    }

    // Set custom handler for location field.
    $handler = 'views';
    $handler_settings = [
      'view' => [
        'view_name' => 'rothamsted_quick_location_reference',
        'display_name' => 'entity_reference',
        'arguments' => [],
      ],
    ];
    $fields['location']->setSetting('handler', $handler);
    $fields['location']->setSetting('handler_settings', $handler_settings);

    /* Create remaining special field types. */

    // Experiment file link fields.
    $fields['experiment_plan_link'] = BundleFieldDefinition::create('link')
      ->setLabel($this->t('Experiment plan'))
      ->setRequired(FALSE)
      ->setRevisionable(TRUE)
      ->setSettings([
        'title' => DRUPAL_DISABLED,
        'link_type' => LinkItemInterface::LINK_EXTERNAL,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['experiment_file_link'] = BundleFieldDefinition::create('link')
      ->setLabel($this->t('Experiment file'))
      ->setRequired(FALSE)
      ->setRevisionable(TRUE)
      ->setSettings([
        'title' => DRUPAL_DISABLED,
        'link_type' => LinkItemInterface::LINK_EXTERNAL,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Column descriptors.
    $fields['column_descriptors'] = BundleFieldDefinition::create('json_native')
      ->setLabel($this->t('Column descriptors'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Integer year fields.
    $fields['drilling_year'] = BundleFieldDefinition::create('integer')
      ->setLabel($this->t('Drilling Year'))
      ->setDescription($this->t('The year the experiment is to be drilled.'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('min', 1800)
      ->setSetting('max', 3000);
    $fields['harvest_year'] = BundleFieldDefinition::create('integer')
      ->setLabel($this->t('Harvest Year'))
      ->setDescription($this->t('The year the experiment is to be harvested.'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('min', 1800)
      ->setSetting('max', 3000);

    // Additional fields added with 2.10.
    $fields['study_description'] = BundleFieldDefinition::create('text_long')
      ->setLabel($this->t('Description'))
      ->setDescription($this->t('A description of the study period.'))
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
    $fields['study_number'] = BundleFieldDefinition::create('integer')
      ->setLabel($this->t('Study number'))
      ->setDescription($this->t('A consecutive number that can be used to identify the study.'))
      ->setRevisionable(TRUE)
      ->setSetting('min', 0)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'number_integer',
        'label' => 'inline',
      ]);
    $fields['start'] = BundleFieldDefinition::create('datetime')
      ->setLabel($this->t('Start date'))
      ->setDescription($this->t('The start date of the plan.'))
      ->setRevisionable(TRUE)
      ->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'datetime_default',
        'label' => 'inline',
        'settings' => [
          'format_type' => 'farm_rothamsted_date',
        ],
      ]);
    $fields['end'] = BundleFieldDefinition::create('datetime')
      ->setLabel($this->t('End date'))
      ->setDescription($this->t('The end date of the plan.'))
      ->setRevisionable(TRUE)
      ->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'datetime_default',
        'label' => 'inline',
        'settings' => [
          'format_type' => 'farm_rothamsted_date',
        ],
      ]);
    $fields['current_phase'] = BundleFieldDefinition::create('string')
      ->setLabel($this->t('Current Phase'))
      ->setDescription($this->t('The current phase that the rotation is in.'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'label' => 'inline',
      ]);
    $fields['cost_code_allocation'] = BundleFieldDefinition::create('text_long')
      ->setLabel($this->t('Cost code allocation'))
      ->setDescription($this->t('List the cost codes and percentage allocations.'))
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
    $fields['amendments'] = BundleFieldDefinition::create('text_long')
      ->setLabel($this->t('Amendments'))
      ->setDescription($this->t('A description of any changes made to the experiment.'))
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

    $restriction_fields = [
      'restriction_crop' => [
        'boolean' => [
          'label' => $this->t('Crop Management Restrictions'),
          'description' => $this->t('Are there any restrictions that affect how the crop(s) in the experiment will be managed (cultivations, pesticide applications, etc?)'),
        ],
        'text' => [
          'label' => $this->t('Description of Crop Management Restrictions'),
          'description' => $this->t('Please describe the crop management restrictions. Note: All aspects of crop management will need to be discussed in detail with the trials team once the proposal has been approved.'),
        ],
      ],
      'restriction_gm' => [
        'boolean' => [
          'label' => $this->t('Genetically Modified (GM) Material'),
          'description' => $this->t('Is there any GM material being used?'),
        ],
        'text' => [
          'label' => $this->t('Description of GM material'),
          'description' => $this->t('Please describe the GM materials.'),
        ],
      ],
      'restriction_ge' => [
        'boolean' => [
          'label' => $this->t('Genetically Edited (GE) Material'),
          'description' => $this->t('Is there any GE material being used?'),
        ],
        'text' => [
          'label' => $this->t('Description of GE material'),
          'description' => $this->t('Please describe the GE materials.'),
        ],
      ],
      'restriction_off_label' => [
        'boolean' => [
          'label' => $this->t('Off-label Products'),
          'description' => $this->t('Is there a requirement for off-label or uncertified products (e.g. pesticides, growth regulators) to be applied?'),
        ],
        'text' => [
          'label' => $this->t('Description of off-label products'),
          'description' => $this->t('Please describe the off-label products.'),
        ],
      ],
      'restriction_licence_perm' => [
        'boolean' => [
          'label' => $this->t('Licence and Permissions'),
          'description' => $this->t('Do you need a specific licence or other permission?'),
        ],
        'text' => [
          'label' => $this->t('Licence and Permissions'),
          'description' => $this->t('Please describe the licence/permission restrictions.'),
        ],
      ],
    ];

    // Add boolean and text_long field for each restriction.
    foreach ($restriction_fields as $restriction_field_id => $restriction_field_info) {
      $fields[$restriction_field_id] = BundleFieldDefinition::create('boolean')
        ->setLabel($restriction_field_info['boolean']['label'])
        ->setDescription($restriction_field_info['boolean']['description'])
        ->setRevisionable(TRUE)
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayOptions('form', [
          'type' => 'boolean_checkbox',
        ])
        ->setDisplayConfigurable('view', TRUE)
        ->setDisplayOptions('view', [
          'type' => 'boolean',
          'label' => 'inline',
          'settings' => [
            'format' => 'yes-no',
          ],
        ]);
      $description_field_id = $restriction_field_id . '_desc';
      $fields[$description_field_id] = BundleFieldDefinition::create('text_long')
        ->setLabel($restriction_field_info['text']['label'])
        ->setDescription($restriction_field_info['text']['description'])
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

    $fields['restriction_other'] = BundleFieldDefinition::create('text_long')
      ->setLabel($this->t('Other restrictions'))
      ->setDescription($this->t('If there are any other restrictions not covered above, please add them below'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
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

    $fields['mgmt_seed_provision'] = BundleFieldDefinition::create('list_string')
      ->setLabel($this->t('Seed Provision'))
      ->setDescription($this->t('Please state who will provide the seed.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setRevisionable(TRUE)
      ->setSetting('allowed_values', [
        'sponsor' => $this->t('Sponsor'),
        'farm' => $this->t('Farm'),
        'other' => $this->t('Other'),
        'na' => $this->t('Not applicable'),
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'label' => 'inline',
      ]);

    $management_fields = [
      'seed_trt' => [
        'label' => $this->t('Seed treatments'),
        'description' => $this->t('Please specify any requirements relating ot seed treatments.'),
      ],
      'variety_notes' => [
        'label' => $this->t('Variety notes'),
        'description' => $this->t('Any other notes about the varieties requested/selected.'),
      ],
      'ploughing' => [
        'label' => $this->t('Ploughing'),
        'description' => $this->t('Detail any management related to ploughing.'),
      ],
      'levelling' => [
        'label' => $this->t('Levelling'),
        'description' => $this->t('Detail any management related to levelling.'),
      ],
      'seed_cultivation' => [
        'label' => $this->t('Seed bed cultivation'),
        'description' => $this->t('Detail any management related to seed bed cultivation.'),
      ],
      'planting_date' => [
        'label' => $this->t('Planting dates'),
        'description' => $this->t('Request specific planting dates.'),
      ],
      'seed_rate' => [
        'label' => $this->t('Seed rate'),
        'description' => $this->t('Request specific seed rates.'),
      ],
      'drilling_rate' => [
        'label' => $this->t('Drilling rate'),
        'description' => $this->t('Request specific drilling rates.'),
      ],
      'plant_estab' => [
        'label' => $this->t('Plant Establishment'),
        'description' => $this->t('Detail any management relating to plant establishment.'),
      ],
      'fungicide' => [
        'label' => $this->t('Fungicides'),
        'description' => $this->t('Please specify any requirements relating to fungicides and plant pathogen management.'),
      ],
      'herbicide' => [
        'label' => $this->t('Herbicides'),
        'description' => $this->t('Please specify any requirements relating to herbicides and weed management.'),
      ],
      'insecticide' => [
        'label' => $this->t('Insecticides'),
        'description' => $this->t('Please specify any requirements relating to insecticides and pest management.'),
      ],
      'nematicide' => [
        'label' => $this->t('Nematicides'),
        'description' => $this->t('Please specify any requirements relating to nematodes and nematicides.'),
      ],
      'molluscicide' => [
        'label' => $this->t('Molluscicides'),
        'description' => $this->t('Please specify any requirements relating to slugs, snails and molluscicide management.'),
      ],
      'pgr' => [
        'label' => $this->t('Plant growth regulators (PGR)'),
        'description' => $this->t('Please specify any requirements relating to lodging and plant growth regulators.'),
      ],
      'irrigation' => [
        'label' => $this->t('Irrigation'),
        'description' => $this->t('Please specify any requirements relating to irrigation.'),
      ],
      'nitrogen' => [
        'label' => $this->t('Nitrogen (N)'),
        'description' => $this->t('Please specify any nitrogen management requests.'),
      ],
      'potassium' => [
        'label' => $this->t('Potassium (P)'),
        'description' => $this->t('Please specify any potassium management requests.'),
      ],
      'phosphorous' => [
        'label' => $this->t('Phosphorous (K)'),
        'description' => $this->t('Please specify any phosphorous management requests.'),
      ],
      'magnesium' => [
        'label' => $this->t('Magnesium (Mg)'),
        'description' => $this->t('Please specify any magnesium management requests.'),
      ],
      'sulphur' => [
        'label' => $this->t('Sulphur (S)'),
        'description' => $this->t('Please specify any sulphur management requests.'),
      ],
      'micronutrients' => [
        'label' => $this->t('Micronutrients'),
        'description' => $this->t('Please specify any micronutrient management requests.'),
      ],
      'ph' => [
        'label' => $this->t('Liming (pH)'),
        'description' => $this->t('Please specify any pH management requests.'),
      ],
      'pre_harvest' => [
        'label' => $this->t('Pre-harvest sampling'),
        'description' => $this->t('Describe any pre-harvest sampling.'),
      ],
      'grain_samples' => [
        'label' => $this->t('Grain samples'),
        'description' => $this->t('Do you require any grain samples?'),
      ],
      'grain_harvest' => [
        'label' => $this->t('Grain harvest instructions'),
        'description' => $this->t('Please specify any grain handling instructions.'),
      ],
      'straw_samples' => [
        'label' => $this->t('Straw samples'),
        'description' => $this->t('Do you require straw samples?'),
      ],
      'straw_harvest' => [
        'label' => $this->t('Straw harvest instructions'),
        'description' => $this->t('Please specify any straw harvest instructions.'),
      ],
      'post_harvest' => [
        'label' => $this->t('Post-harvest management'),
        'description' => $this->t('Please specify any requirements for post-harvest management.'),
      ],
      'post_harvest_interval' => [
        'label' => $this->t('Post-harvest interval'),
        'description' => $this->t('Please specify a post-harvest interval if needed.'),
      ],
      'post_harvest_sampling' => [
        'label' => $this->t('Post-harvest sampling'),
        'description' => $this->t('Please describe any post-harvest sampling.'),
      ],
      'physical_obstructions' => [
        'label' => $this->t('Physical obstructions'),
        'description' => $this->t('Are there any physical obstructions in the field that will interfere with farm equipment and general management of the experiment?'),
      ],
      'other' => [
        'label' => $this->t('Other'),
        'description' => $this->t('Any other issues relating to the experiment management.'),
      ],
    ];
    foreach ($management_fields as $management_field_id => $management_field_info) {
      $fields["mgmt_$management_field_id"] = BundleFieldDefinition::create('text_long')
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

    return $fields;
  }

}
