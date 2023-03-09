<?php

namespace Drupal\farm_rothamsted_experiment_research\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\RevisionLogEntityTrait;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\user\EntityOwnerTrait;
use Drupal\user\UserInterface;

/**
 * Defines the rothamsted design entity class.
 *
 * @ContentEntityType(
 *   id = "rothamsted_design",
 *   label = @Translation("Experiment Design"),
 *   label_collection = @Translation("Experiment Designs"),
 *   label_singular = @Translation("experiment design"),
 *   label_plural = @Translation("experiment designs"),
 *   handlers = {
 *     "access" = "\Drupal\entity\UncacheableEntityAccessControlHandler",
 *     "list_builder" = "Drupal\farm_rothamsted_experiment_research\RothamstedEntityListBuilder",
 *     "permission_provider" = "\Drupal\entity\UncacheableEntityPermissionProvider",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\farm_rothamsted_experiment_research\Form\DesignEntityForm",
 *       "edit" = "Drupal\farm_rothamsted_experiment_research\Form\DesignEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\entity\Routing\AdminHtmlRouteProvider",
 *       "revision" = "\Drupal\entity\Routing\RevisionRouteProvider",
 *     },
 *     "local_task_provider" = {
 *       "default" = "\Drupal\farm_rothamsted_experiment_research\Menu\DefaultSecondaryTaskProvider",
 *     },
 *   },
 *   base_table = "rothamsted_design",
 *   data_table = "rothamsted_design_data",
 *   revision_table = "rothamsted_design_revision",
 *   translatable = TRUE,
 *   revisionable = TRUE,
 *   show_revision_ui = TRUE,
 *   admin_permission = "administer rothamsted designs",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "revision" = "revision_id",
 *     "label" = "name",
 *     "owner" = "uid",
 *     "langcode" = "langcode",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_user",
 *     "revision_created" = "revision_created",
 *     "revision_log_message" = "revision_log_message",
 *   },
 *   links = {
 *     "collection" = "/rothamsted/design",
 *     "canonical" = "/rothamsted/design/{rothamsted_design}",
 *     "add-form" = "/rothamsted/design/add",
 *     "edit-form" = "/rothamsted/design/{rothamsted_design}/edit",
 *     "delete-form" = "/rothamsted/design/{rothamsted_design}/delete",
 *     "version-history" = "/rothamsted/design/{rothamsted_design}/revisions",
 *     "revision" = "/rothamsted/design/{rothamsted_design}/revisions/{rothamsted_design_revision}/view",
 *     "revision-revert-form" = "/rothamsted/design/{rothamsted_design}/revisions/{rothamsted_design_revision}/revert",
 *   },
 * )
 */
class RothamstedDesign extends RevisionableContentEntityBase implements RothamstedDesignInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;
  use RevisionLogEntityTrait;

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getName();
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);
    $fields += static::revisionLogBaseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the experiment design.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setSetting('text_processing', 0)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ]);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setDescription(t('The user ID of author of the experiment design.'))
      ->setRevisionable(TRUE)
      ->setDefaultValueCallback(static::class . '::getCurrentUserId')
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'region' => 'hidden',
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the experiment design was created.'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'region' => 'hidden',
      ]);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the experiment design was last edited.'))
      ->setRevisionable(TRUE);

    $fields['experiment'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Related Experiment'))
      ->setDescription(t('Please select the experiments that this design relates to.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setSetting('target_type', 'rothamsted_experiment')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
      ]);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setDescription(t('A description of the experiment design.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'inline',
      ]);

    $fields['blocking_structure'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Blocking Structure'))
      ->setDescription(t('The blocking structure used for the experiment design.'))
      ->setRevisionable(TRUE)
      ->setSetting('allowed_values', [
        'crd' => t('Completely Randomised Design'),
        'rcbd' => t('Randomised Complete Block Design'),
        'rbd' => t('Resolvable Block Design'),
        'nrbd' => t('Non-resolvable Block Design'),
        'rrcd' => t('Resolvable Row-Column Design'),
        'nrrcd' => t('Non-resolvable Row-Column Design'),
        'spd' => t('Split Plot Design'),
        'ad' => t('Augmented Design'),
        'nr' => t('Not Randomised'),
        'other' => t('Other'),
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'settings' => [
          'size' => 25,
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'label' => 'inline',
      ]);

    $fields['statistical_design'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Statistical Design'))
      ->setDescription(t('The statistical design associated with the experiment and blocking structure.'))
      ->setRevisionable(TRUE)
      ->setSetting('allowed_values_function', 'farm_rothamsted_experiment_research_statistical_design_field_allowed_values')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'settings' => [
          'size' => 25,
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'label' => 'inline',
      ]);

    $fields['blocking_constraint'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Additional Blocking Constraints'))
      ->setDescription(t('Any additional blocking constraints associated with the experiment design.'))
      ->setRevisionable(TRUE)
      ->setSetting('allowed_values', [
        't-latinization' => t('T-Latinization'),
        'spatial_standards' => t('Spatial Standards'),
        'spatial_design' => t('Spatial Design'),
        'unequal_replication' => t('Unequal Replication'),
        'other' => t('Other'),
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'settings' => [
          'size' => 25,
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'label' => 'inline',
      ]);

    $fields['start'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Start year'))
      ->setDescription(t('The start year of the experiment design.'))
      ->setRevisionable(TRUE)
      ->setSetting('min', 1800)
      ->setSetting('max', 3000)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'number',
        'label' => 'inline',
      ]);

    $fields['end'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('End year'))
      ->setDescription(t('The end year of the experiment design.'))
      ->setRevisionable(TRUE)
      ->setSetting('min', 1800)
      ->setSetting('max', 3000)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'number',
        'label' => 'inline',
      ]);

    $fields['statistician'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Statisticians'))
      ->setDescription(t('The statisticians responsible for the statistical design.'))
      ->setRevisionable(TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'rothamsted_researcher')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
      ]);

    $fields['model'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Statistical Models'))
      ->setDescription(t('The statistical model associated with the experiment.'))
      ->setRevisionable(TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'inline',
      ]);

    // Treatments.
    $fields['treatment'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Treatments'))
      ->setDescription(t('Describe the treatments for this statistical design.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'inline',
      ]);

    $fields['num_treatments'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Number of treatments'))
      ->setDescription(t('The number of treatmetns.'))
      ->setRevisionable(TRUE)
      ->setSetting('min', 0)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'number',
        'label' => 'inline',
      ]);

    $fields['num_factor_level_combinations'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Number of Factor Level Combinations'))
      ->setDescription(t('The number of factor level combinations.'))
      ->setRevisionable(TRUE)
      ->setSetting('min', 0)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'number',
        'label' => 'inline',
      ]);

    $fields['num_replicates'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Number of Replicates'))
      ->setDescription(t('The number of replicates for each factor level combination.'))
      ->setRevisionable(TRUE)
      ->setSetting('min', 0)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'number',
        'label' => 'inline',
      ]);

    $fields['notes'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Notes'))
      ->setDescription(t('Any other additional notes relating to the design of the experiment.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'inline',
      ]);

    // Layout.
    $fields['layout_description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Layout description'))
      ->setDescription(t('A description of the experiment layout.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'inline',
      ]);

    $fields['horizontal_row_spacing'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Horizontal row spacing'))
      ->setDescription(t('The spacing of the horizontal rows between the plots. For example, 3 rows of 1.5m followed by 1 row of 3m.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'inline',
      ]);

    $fields['vertical_row_spacing'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Vertical row spacing'))
      ->setDescription(t('The spacing of the vertical rows between the plots. For example, 3 rows of 1.5m followed by 1 row of 3m.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'inline',
      ]);

    // Layout number fields.
    $number_fields = [
      'plot_length' => [
        'label' => t('Plot length'),
        'description' => t('The length of the plots.'),
      ],
      'plot_width' => [
        'label' => t('Plot width'),
        'description' => t('The width of the plots.'),
      ],
      'plot_area' => [
        'label' => t('Plot area'),
        'description' => t('The area of the plots.'),
      ],
      'total_plot_area' => [
        'label' => t('Total plot area'),
        'description' => t('The total area covered by the plots.'),
      ],
      'experiment_area' => [
        'label' => t('Experiment area'),
        'description' => t('The total area covered by the experiment.'),
      ],
      'num_rows' => [
        'label' => t('Number of rows'),
        'description' => t('The number of rows in the experiment.'),
      ],
      'num_columns' => [
        'label' => t('Number of columns'),
        'description' => t('The number of columns in the experiment.'),
      ],
      'num_blocks' => [
        'label' => t('Number of blocks'),
        'description' => t('The number of blocks in the experiment.'),
      ],
      'num_plots_block' => [
        'label' => t('Number of plots per block'),
        'description' => t('The number of plots per block.'),
      ],
      'num_mainplots' => [
        'label' => t('Number of main plots'),
        'description' => t('The number of main plots in the experiment.'),
      ],
      'num_subplots_mainplots' => [
        'label' => t('Number of subplots per main plot'),
        'description' => t('The number of subplots per main plot.'),
      ],
      'num_subplots' => [
        'label' => t('Number of subplots'),
        'description' => t('The number of subplots in the experiment.'),
      ],
      'num_subsubplots_subplot' => [
        'label' => t('Number of sub-subplots per subplot'),
        'description' => t('The number of sub-subplots per subplot.'),
      ],
      'num_subsubplots' => [
        'label' => t('Number of sub-subplots'),
        'description' => t('The number of sub-subplots in the experiment.'),
      ],
    ];

    // Create each number field.
    foreach ($number_fields as $field_id => $field_info) {
      $fields[$field_id] = BaseFieldDefinition::create('integer')
        ->setLabel($field_info['label'])
        ->setDescription($field_info['description'])
        ->setRevisionable(TRUE)
        ->setSetting('min', 0)
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayOptions('form', [
          'type' => 'number',
        ])
        ->setDisplayConfigurable('view', TRUE)
        ->setDisplayOptions('view', [
          'type' => 'number',
          'label' => 'inline',
        ]);
    }

    return $fields;
  }

}
