<?php

namespace Drupal\farm_rothamsted_experiment_research\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\RevisionLogEntityTrait;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\link\LinkItemInterface;
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
 *     "permission_provider" = "Drupal\farm_rothamsted_experiment_research\ResearchEntityPermissionProvider",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\entity\EntityViewsData",
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
 *       "default" = "\Drupal\farm_ui_menu\Menu\DefaultSecondaryLocalTaskProvider",
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
      ->setLabel(t('Name of Design Period'))
      ->setDescription(t('The name of the design period. The standard naming convention is the experiment name, followed by the design iteration and start year. For example Broadbalk: 3rd Design Period (1968 - )'))
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

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setDescription(t('The status of the experiment design.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'requested' => t('Requested'),
        'planning' => t('Planning'),
        'active' => t('Active'),
        'archived' => t('Archived'),
      ])
      ->setDefaultValue('requested')
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

    $fields['status_notes'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Status notes'))
      ->setDescription(t('Any notes about the design status.'))
      ->setRevisionable(TRUE)
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

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Design description'))
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

    $fields['design_changes'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Changes from previous design'))
      ->setDescription(t('Where relevant, please describe any changes from the previous statistical design and why the changes were made.'))
      ->setRevisionable(TRUE)
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

    $fields['previous_cropping'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Previous Cropping'))
      ->setDescription(t('The crop(s) which were grown in the same location immediately before the experiment.'))
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
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'settings' => [
          'link' => TRUE,
        ],
      ]);

    $fields['rotation_treatment'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Rotation as Treatment'))
      ->setDescription(t('Is the rotation a treatment in this experiment design? Rotations which are part of the treatment structure should be added via the plot attributes.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setDefaultValue(0)
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

    $fields['rotation_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Rotation name'))
      ->setDescription(t('The name of the rotation.'))
      ->setRevisionable(TRUE)
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

    $fields['rotation_description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Rotation description'))
      ->setDescription(t('A description of the rotation.'))
      ->setRevisionable(TRUE)
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

    $fields['rotation_crop'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Rotation Crops'))
      ->setDescription(t('The crops in the rotation.'))
      ->setRevisionable(TRUE)
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
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
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'settings' => [
          'link' => TRUE,
        ],
      ]);

    $fields['rotation_phasing'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Rotation phasing'))
      ->setDescription(t('The phasing of the rotation. E.g. winter wheat - winter oilseed rape - autumn cover crop - spring beans.'))
      ->setRevisionable(TRUE)
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

    $fields['rotation_notes'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Rotation notes'))
      ->setDescription(t('Any additional notes about the rotation.'))
      ->setRevisionable(TRUE)
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

    // Statistical design.
    $fields['objective'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Objective'))
      ->setDescription(t('The objectives of the experiment design.'))
      ->setRevisionable(TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
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

    $fields['num_treatments'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Number of Treatment Factors'))
      ->setDescription(t('The number of treatment factors being tested in the experiment, where a treatment factor is a variable under the control of the experimenter (sometimes also called explanatory variables) with two or more levels.'))
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

    $fields['treatment'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Treatment Factors'))
      ->setDescription(t('A description of the treatment factor(s) being tested in the experiment, with a list of the factor levels where applicable. Please add a new box for each treatment factor. For example: "Fungicide exposure (high, medium, low, none)" in one box and "Plant Breed Line (Cadenza, KWS Zyatt, KWS Extase) in another.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'inline',
      ]);

    $fields['dependent_variables'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Dependant Variables'))
      ->setDescription(t('Describe the dependant variables, adding a new box for each variable. These are also called outcome or response variables, and are the measurement values that are being predicted (or their variation measured) by this experiment.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'inline',
      ]);

    $fields['hypothesis'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Hypotheses'))
      ->setDescription(t('The hypotheses that the design is testing. This must define your predictions. See https://scientific-publishing.webshop.elsevier.com/manuscript-preparation/what-how-write-good-hypothesis-research/'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
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
      ->setRequired(TRUE)
      ->setDefaultValue('none')
      ->setRevisionable(TRUE)
      ->setSetting('allowed_values', [
        't-latinization' => t('T-Latinization'),
        'spatial_standards' => t('Spatial Standards'),
        'spatial_design' => t('Spatial Design'),
        'unequal_replication' => t('Unequal Replication'),
        'other' => t('Other (see description)'),
        'none' => t('None'),
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

    $fields['num_factor_level_combinations'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Number of Factor Level Combinations'))
      ->setDescription(t('The number of unique treatments, where a unique treatment might be a combination of factor levels from two different treatment factors. For example, if you have two treatments factors, one for Fungicide Exposure with four factor levels (high, medium, low, none) and a second treatment factor for Wheat Variety with two factor levels (Variety 1 and Variety 2) and all four fungicide treatments are applied to each of the two varieties, then there are 8 factor level combinations (Variety 1 with high fungicide exposure, Variety 2 with high fungicide exposure, Variety 1 with medium fungicide exposure, Variety 2 with medium fungicide exposure, etc).'))
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

    $fields['num_replicates'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Number of Replicates'))
      ->setDescription(t('The number of times each factor level combination is repeated in the experiment.'))
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
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'region' => 'hidden',
      ]);

    $fields['vertical_row_spacing'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Vertical row spacing'))
      ->setDescription(t('The spacing of the vertical rows between the plots. For example, 3 rows of 1.5m followed by 1 row of 3m.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'region' => 'hidden',
      ]);

    $fields['plot_non_standard'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Varying plot sizes'))
      ->setDescription(t('Check if the plots vary in size across the experiment.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setDefaultValue(0)
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

    // Layout number fields.
    $number_fields = [
      'plot_length' => [
        'type' => 'float',
        'label' => t('Plot length'),
        'description' => t('The length of the plots.'),
        'suffix' => 'm',
        'hidden' => TRUE,
      ],
      'plot_width' => [
        'type' => 'float',
        'label' => t('Plot width'),
        'description' => t('The width of the plots.'),
        'suffix' => 'm',
        'hidden' => TRUE,
      ],
      'plot_area' => [
        'type' => 'float',
        'label' => t('Plot area'),
        'description' => t('The area of the plots.'),
        'suffix' => 'm2',
        'hidden' => TRUE,
      ],
      'total_plot_area' => [
        'type' => 'float',
        'label' => t('Total plot area'),
        'description' => t('The total area covered by the plots.'),
        'suffix' => 'm2',
      ],
      'experiment_area' => [
        'type' => 'float',
        'label' => t('Experiment area'),
        'description' => t('The total area covered by the experiment.'),
        'suffix' => 'm2',
      ],
      'num_rows' => [
        'type' => 'integer',
        'label' => t('Number of rows'),
        'description' => t('The number of rows in the experiment.'),
        'hidden' => TRUE,
      ],
      'num_columns' => [
        'type' => 'integer',
        'label' => t('Number of columns'),
        'description' => t('The number of columns in the experiment.'),
        'hidden' => TRUE,
      ],
      'num_blocks' => [
        'type' => 'integer',
        'label' => t('Number of blocks'),
        'description' => t('The number of blocks in the experiment.'),
        'hidden' => TRUE,
      ],
      'num_plots_block' => [
        'type' => 'integer',
        'label' => t('Number of main plots per block'),
        'description' => t('The number of main plots per block.'),
        'hidden' => TRUE,
      ],
      'num_mainplots' => [
        'type' => 'integer',
        'label' => t('Number of main plots'),
        'description' => t('The number of main plots in the experiment.'),
        'hidden' => TRUE,
      ],
      'num_subplots_mainplots' => [
        'type' => 'integer',
        'label' => t('Number of subplots per main plot'),
        'description' => t('The number of subplots per main plot.'),
        'hidden' => TRUE,
      ],
      'num_subplots' => [
        'type' => 'integer',
        'label' => t('Number of subplots'),
        'description' => t('The number of subplots in the experiment.'),
        'hidden' => TRUE,
      ],
      'num_subsubplots_subplot' => [
        'type' => 'integer',
        'label' => t('Number of sub-subplots per subplot'),
        'description' => t('The number of sub-subplots per subplot.'),
        'hidden' => TRUE,
      ],
      'num_subsubplots' => [
        'type' => 'integer',
        'label' => t('Number of sub-subplots'),
        'description' => t('The number of sub-subplots in the experiment.'),
        'hidden' => TRUE,
      ],
    ];

    // Create each number field.
    foreach ($number_fields as $field_id => $field_info) {
      $fields[$field_id] = BaseFieldDefinition::create($field_info['type'])
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

      // Hide fields if specified.
      if ($field_info['hidden'] ?? FALSE) {
        $fields[$field_id]->setDisplayOptions('form', ['region' => 'hidden']);
        $fields[$field_id]->setDisplayOptions('view', ['region' => 'hidden']);
      }

      // Add suffix.
      if (isset($field_info['suffix'])) {
        $fields[$field_id]->setSetting('suffix', $field_info['suffix']);
      }
    }

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
        ->setRevisionable(TRUE)
        ->setRequired(TRUE)
        ->setDefaultValue(0)
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
      $description_field_id = $restriction_field_id . '_desc';
      $fields[$description_field_id] = BaseFieldDefinition::create('text_long')
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

    $fields['restriction_other'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Other restrictions'))
      ->setDescription(t('If there are any other restrictions not covered above, please add them below'))
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

    $fields['mgmt_seed_provision'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Seed Provision'))
      ->setDescription(t('Please state who will provide the seed.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setRevisionable(TRUE)
      ->setSetting('allowed_values', [
        'sponsor' => t('Sponsor'),
        'farm' => t('Farm'),
        'other' => t('Other'),
        'na' => t('Not applicable'),
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
      'seed_treatments' => [
        'label' => t('Seed treatments'),
        'description' => t('Please specify any requirements relating to seed treatments.'),
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
      'drill_spacing' => [
        'label' => t('Drill spacing'),
        'description' => t('Request specific drill spacing.'),
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
      'organic_amendments' => [
        'label' => t('Organic amendments'),
        'description' => t('Request specific organic amendments (farmyard manure, poultry manure, compost, etc).'),
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
      'other' => [
        'label' => t('Other'),
        'description' => t('Any other issues relating to the experiment management.'),
      ],
    ];
    foreach ($management_fields as $management_field_id => $management_field_info) {

      // Create text_long field.
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

    $image_field_settings = $file_settings + [
      'file_extensions' => 'png gif jpg jpeg',
    ];
    $fields['image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Image'))
      ->setDescription(t('Upload files associated with this design.'))
      ->setRevisionable(TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSettings($image_field_settings)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'image_image',
        'settings' => [
          'preview_image_style' => 'medium',
          'progress_indicator' => 'throbber',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'image',
        'label' => 'visually_hidden',
        'settings' => [
          'image_style' => 'large',
          'image_link' => 'file',
        ],
      ]);

    $fields['link'] = BaseFieldDefinition::create('link')
      ->setLabel(t('Links'))
      ->setDescription(t('Links to external website and documents associated with the design.'))
      ->setRevisionable(TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSettings([
        'title' => DRUPAL_OPTIONAL,
        'link_type' => LinkItemInterface::LINK_EXTERNAL,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'link',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'link',
      ]);

    return $fields;
  }

}
