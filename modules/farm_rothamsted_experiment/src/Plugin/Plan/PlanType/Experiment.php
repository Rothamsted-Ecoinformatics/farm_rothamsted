<?php

namespace Drupal\farm_rothamsted_experiment\Plugin\Plan\PlanType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
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

    // Set weight of asset field higher than locations field.
    $fields['asset']->setDisplayOptions('form', [
      'type' => 'entity_reference_autocomplete',
      'weight' => 5,
    ]);
    $fields['asset']->setDisplayOptions('view', [
      'type' => 'entity_reference_label',
      'label' => 'inline',
      'weight' => 5,
      'settings' => [
        'link' => TRUE,
      ],
    ]);

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
        'weight' => [
          'form' => 0,
          'view' => 0,
        ],
      ],
      'study_period_id' => [
        'type' => 'string',
        'label' => $this->t('Study Period ID'),
        'description' => $this->t('The unique identifier for the study, for example 2020/R/CS/790.'),
        'required' => TRUE,
        'weight' => [
          'form' => 5,
          'view' => 5,
        ],
      ],
      'cost_code' => [
        'type' => 'string',
        'label' => $this->t('Cost Code'),
        'description' => $this->t('The cost code associated with the project.'),
        'multiple' => TRUE,
        'weight' => [
          'form' => 40,
          'view' => 40,
        ],
      ],
      'location' => [
        'type' => 'entity_reference',
        'label' => $this->t('Field Location(s)'),
        'description' => $this->t('The field(s) or location(s) of the experiment.'),
        'target_type' => 'asset',
        'target_bundle' => 'land',
        'multiple' => TRUE,
        'weight' => [
          'form' => 0,
          'view' => 0,
        ],
      ],
      // Trial design fields.
      'plant_type' => [
        'type' => 'entity_reference',
        'label' => $this->t('Crop(s)'),
        'description' => $this->t('The crop(s) planted in the experiment.'),
        'target_type' => 'taxonomy_term',
        'target_bundle' => 'crop_family',
        'auto_create' => FALSE,
        'multiple' => TRUE,
        'weight' => [
          'form' => 20,
          'view' => 20,
        ],
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
      ->setDisplayOptions('form', [
        'type' => 'link_default',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'link',
        'label' => 'inline',
        'view' => 0,
      ]);
    $fields['experiment_file_link'] = BundleFieldDefinition::create('link')
      ->setLabel($this->t('Experiment file'))
      ->setRequired(FALSE)
      ->setRevisionable(TRUE)
      ->setSettings([
        'title' => DRUPAL_DISABLED,
        'link_type' => LinkItemInterface::LINK_EXTERNAL,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'link_default',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'link',
        'label' => 'inline',
        'view' => 5,
      ]);
    $fields['other_links'] = BundleFieldDefinition::create('link')
      ->setLabel($this->t('Other links'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setRequired(FALSE)
      ->setRevisionable(TRUE)
      ->setSettings([
        'title' => DRUPAL_OPTIONAL,
        'link_type' => LinkItemInterface::LINK_EXTERNAL,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'link_default',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'link',
        'label' => 'inline',
        'view' => 10,
      ]);

    // Column descriptors.
    $fields['column_descriptors'] = BundleFieldDefinition::create('json_native')
      ->setLabel($this->t('Column descriptors'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Integer year fields.
    $fields['drilling_year'] = BundleFieldDefinition::create('integer')
      ->setLabel($this->t('Planting Year'))
      ->setDescription($this->t('The planting year for the study.'))
      ->setRevisionable(TRUE)
      ->setSetting('min', 1800)
      ->setSetting('max', 3000)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 30,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'number_integer',
        'label' => 'inline',
        'view' => 30,
      ]);
    $fields['harvest_year'] = BundleFieldDefinition::create('integer')
      ->setLabel($this->t('Harvest Year'))
      ->setDescription($this->t('The year the experiment is to be harvested.'))
      ->setRevisionable(TRUE)
      ->setSetting('min', 1800)
      ->setSetting('max', 3000)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 35,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'number_integer',
        'label' => 'inline',
        'weight' => 35,
      ]);

    // Additional fields added with 2.10.
    $fields['study_description'] = BundleFieldDefinition::create('text_long')
      ->setLabel($this->t('Description'))
      ->setDescription($this->t('A description of the study period.'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'inline',
        'weight' => 10,
      ]);
    $fields['study_number'] = BundleFieldDefinition::create('integer')
      ->setLabel($this->t('Study number'))
      ->setDescription($this->t('A consecutive number that can be used to identify the study.'))
      ->setRevisionable(TRUE)
      ->setSetting('min', 0)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'number_integer',
        'label' => 'inline',
        'weight' => 15,
      ]);
    $fields['current_phase'] = BundleFieldDefinition::create('string')
      ->setLabel($this->t('Current Phase'))
      ->setDescription($this->t('The current phase that the rotation is in.'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 25,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'label' => 'inline',
        'weight' => 25,
      ]);
    $fields['cost_code_allocation'] = BundleFieldDefinition::create('text_long')
      ->setLabel($this->t('Cost code allocation'))
      ->setDescription($this->t('List the cost codes and percentage allocations.'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 45,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'inline',
        'weight' => 45,
      ]);

    return $fields;
  }

}
