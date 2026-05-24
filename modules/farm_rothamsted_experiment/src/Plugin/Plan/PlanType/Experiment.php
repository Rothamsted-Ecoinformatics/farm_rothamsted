<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_experiment\Plugin\Plan\PlanType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity\BundleFieldDefinition;
use Drupal\farm_entity\Attribute\PlanType;
use Drupal\farm_entity\Plugin\Plan\PlanType\FarmPlanType;
use Drupal\link\LinkItemInterface;

/**
 * Provides the experiment plan type.
 *
 * Renamed to be Study Plan in text labels.
 */
#[PlanType(
  id: 'rothamsted_experiment',
  label: new TranslatableMarkup('Study Plan'),
)]
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
        'label' => new TranslatableMarkup('Plots'),
        'description' => new TranslatableMarkup('Plot assets associated with this experiment.'),
        'target_type' => 'asset',
        'target_bundle' => 'plot',
        'multiple' => TRUE,
        'hidden' => TRUE,
      ],
      // General fields.
      'abbreviation' => [
        'type' => 'string',
        'label' => new TranslatableMarkup('Study Abbreviation'),
        'description' => new TranslatableMarkup('An abbreviation of the study name.'),
        'weight' => [
          'form' => 0,
          'view' => 0,
        ],
      ],
      'study_period_id' => [
        'type' => 'string',
        'label' => new TranslatableMarkup('Study Period ID'),
        'description' => new TranslatableMarkup('The unique identifier for the study, for example 2020/R/CS/790.'),
        'required' => TRUE,
        'weight' => [
          'form' => 5,
          'view' => 5,
        ],
      ],
      'cost_code' => [
        'type' => 'string',
        'label' => new TranslatableMarkup('Cost Code'),
        'description' => new TranslatableMarkup('The cost code associated with the project.'),
        'multiple' => TRUE,
        'weight' => [
          'form' => 40,
          'view' => 40,
        ],
      ],
      'location' => [
        'type' => 'entity_reference',
        'label' => new TranslatableMarkup('Field Location(s)'),
        'description' => new TranslatableMarkup('The field(s) or location(s) of the experiment.'),
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
        'label' => new TranslatableMarkup('Crop(s)'),
        'description' => new TranslatableMarkup('The crop(s) planted in the experiment.'),
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
        'label' => new TranslatableMarkup('Status notes'),
        'description' => new TranslatableMarkup('Any notes about the Study plan status.'),
      ],
      'deviations' => [
        'type' => 'text_long',
        'label' => new TranslatableMarkup('Deviations from plan'),
        'description' => new TranslatableMarkup('Any deviations from the original statistical design.'),
        'multiple' => TRUE,
      ],
      'growing_conditions' => [
        'type' => 'text_long',
        'label' => new TranslatableMarkup('Growing Conditions'),
        'description' => new TranslatableMarkup('A description of the growing conditions, where relevant.'),
      ],
      'reason_for_failure' => [
        'type' => 'text_long',
        'label' => new TranslatableMarkup('Reason for Failure'),
        'description' => new TranslatableMarkup('Notes about the cause of crop failure, where relevant.'),
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
    $simple_file_view_display_options = [
      'type' => 'file_uri_plain',
      'label' => 'inline',
    ];
    $fields['columns_file'] = BundleFieldDefinition::create('file')
      ->setLabel(new TranslatableMarkup('Columns'))
      ->setRevisionable(TRUE)
      ->setCardinality(1)
      ->setSettings($file_settings + [
        'description_field' => FALSE,
        'file_extensions' => 'csv',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', $simple_file_view_display_options);
    $fields['column_levels_file'] = BundleFieldDefinition::create('file')
      ->setLabel(new TranslatableMarkup('Column levels'))
      ->setRevisionable(TRUE)
      ->setCardinality(1)
      ->setSettings($file_settings + [
        'description_field' => FALSE,
        'file_extensions' => 'csv',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', $simple_file_view_display_options);
    $fields['plot_attributes_file'] = BundleFieldDefinition::create('file')
      ->setLabel(new TranslatableMarkup('Plot attributes'))
      ->setRevisionable(TRUE)
      ->setCardinality(1)
      ->setSettings($file_settings + [
        'description_field' => FALSE,
        'file_extensions' => 'csv',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', $simple_file_view_display_options);
    $fields['plot_geometry_file'] = BundleFieldDefinition::create('file')
      ->setLabel(new TranslatableMarkup('Plot geometries'))
      ->setRevisionable(TRUE)
      ->setCardinality(1)
      ->setSettings($file_settings + [
        'description_field' => FALSE,
        'file_extensions' => 'geojson',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', $simple_file_view_display_options);

    $fields['agreed_quote'] = BundleFieldDefinition::create('file')
      ->setLabel(new TranslatableMarkup('Agreed Quote'))
      ->setDescription(new TranslatableMarkup('The final agreed quotation for the work proposed.'))
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
      ->setLabel(new TranslatableMarkup('Experiment plan'))
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
      ->setLabel(new TranslatableMarkup('Experiment file'))
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
      ->setLabel(new TranslatableMarkup('Other links'))
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
      ->setLabel(new TranslatableMarkup('Column descriptors'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Integer year fields.
    $fields['drilling_year'] = BundleFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Planting Year'))
      ->setDescription(new TranslatableMarkup('The planting year for the study.'))
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
      ->setLabel(new TranslatableMarkup('Harvest Year'))
      ->setDescription(new TranslatableMarkup('The year the experiment is to be harvested.'))
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
      ->setLabel(new TranslatableMarkup('Description'))
      ->setDescription(new TranslatableMarkup('A description of the study period.'))
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
      ->setLabel(new TranslatableMarkup('Study number'))
      ->setDescription(new TranslatableMarkup('A consecutive number that can be used to identify the study.'))
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
      ->setLabel(new TranslatableMarkup('Current Phase'))
      ->setDescription(new TranslatableMarkup('The current phase that the rotation is in.'))
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
      ->setLabel(new TranslatableMarkup('Cost code allocation'))
      ->setDescription(new TranslatableMarkup('List the cost codes and percentage allocations.'))
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
