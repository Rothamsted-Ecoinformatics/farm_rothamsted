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
        'label' => $this->t('Study Abbreviation'),
        'description' => $this->t('An abbreviation of the study name.'),
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
        'multiple' => TRUE,
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
        'multiple' => TRUE,
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
    // Common file field settings.
    $file_settings = [
      'file_directory' => 'farm/[date:custom:Y]-[date:custom:m]',
      'max_filesize' => '',
      'handler' => 'default:file',
      'handler_settings' => [],
    ];
    $file_field_settings = $file_settings + [
      'description_field' => TRUE,
      'file_extensions' => 'csv doc docx gz geojson gpx kml kmz logz mp3 odp ods odt ogg pdf ppt pptx tar tif tiff txt wav xls xlsx zip',
    ];
    $fields['agreed_quote'] = BundleFieldDefinition::create('file')
      ->setLabel($this->t('Agreed Quote'))
      ->setDescription($this->t('The final agreed quotation for the work proposed.'))
      ->setRevisionable(TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSettings($file_field_settings)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'file_generic',
        'settings' => [
          'progress_indicator' => 'throbber',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'file_table',
        'label' => 'visually_hidden',
        'settings' => [
          'use_description_as_link_text' => TRUE,
        ],
      ]);

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

    return $fields;
  }

}
