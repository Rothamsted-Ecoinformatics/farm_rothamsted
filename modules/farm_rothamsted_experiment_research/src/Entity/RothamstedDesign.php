<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_experiment_research\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\RevisionLogEntityTrait;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity\EntityViewsData;
use Drupal\entity\Routing\AdminHtmlRouteProvider;
use Drupal\entity\Routing\RevisionRouteProvider;
use Drupal\entity\UncacheableEntityAccessControlHandler;
use Drupal\farm_comment\FarmCommentHelper;
use Drupal\farm_rothamsted_experiment_research\Form\DesignEntityForm;
use Drupal\farm_rothamsted_experiment_research\Form\EntityStatusChangeActionForm;
use Drupal\farm_rothamsted_experiment_research\ResearchEntityPermissionProvider;
use Drupal\farm_rothamsted_experiment_research\RothamstedEntityListBuilder;
use Drupal\farm_rothamsted_experiment_research\Routing\EntityStatusChangeRouteProvider;
use Drupal\farm_ui_menu\Menu\DefaultSecondaryLocalTaskProvider;
use Drupal\link\LinkItemInterface;
use Drupal\user\EntityOwnerTrait;
use Drupal\user\UserInterface;

/**
 * Defines the rothamsted design entity class.
 */
#[ContentEntityType(
  id: 'rothamsted_design',
  label: new TranslatableMarkup('Experiment Design'),
  label_collection: new TranslatableMarkup('Experiment Designs'),
  label_singular: new TranslatableMarkup('experiment design'),
  label_plural: new TranslatableMarkup('experiment designs'),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'revision' => 'revision_id',
    'label' => 'name',
    'owner' => 'uid',
    'langcode' => 'langcode',
  ],
  handlers: [
    'access' => UncacheableEntityAccessControlHandler::class,
    'list_builder' => RothamstedEntityListBuilder::class,
    'permission_provider' => ResearchEntityPermissionProvider::class,
    'view_builder' => EntityViewBuilder::class,
    'views_data' => EntityViewsData::class,
    'form' => [
      'add' => DesignEntityForm::class,
      'edit' => DesignEntityForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'entity-status-action-form' => EntityStatusChangeActionForm::class,
    ],
    'route_provider' => [
      'default' => AdminHtmlRouteProvider::class,
      'revision' => RevisionRouteProvider::class,
      'status-change' => EntityStatusChangeRouteProvider::class,
    ],
    'local_task_provider' => [
      'default' => DefaultSecondaryLocalTaskProvider::class,
    ],
  ],
  links: [
    'collection' => '/rothamsted/design',
    'canonical' => '/rothamsted/design/{rothamsted_design}',
    'add-form' => '/rothamsted/design/add',
    'edit-form' => '/rothamsted/design/{rothamsted_design}/edit',
    'delete-form' => '/rothamsted/design/{rothamsted_design}/delete',
    'version-history' => '/rothamsted/design/{rothamsted_design}/revisions',
    'revision' => '/rothamsted/design/{rothamsted_design}/revisions/{rothamsted_design_revision}/view',
    'revision-revert-form' => '/rothamsted/design/{rothamsted_design}/revisions/{rothamsted_design_revision}/revert',
    'entity-status-action-form' => '/rothamsted/design/change-status',
  ],
  admin_permission: 'administer rothamsted designs',
  base_table: 'rothamsted_design',
  data_table: 'rothamsted_design_data',
  revision_table: 'rothamsted_design_revision',
  translatable: TRUE,
  show_revision_ui: TRUE,
  revision_metadata_keys: [
    'revision_user' => 'revision_user',
    'revision_created' => 'revision_created',
    'revision_log_message' => 'revision_log_message',
  ],
)]
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
      ->setLabel(new TranslatableMarkup('Name of Design Period'))
      ->setDescription(new TranslatableMarkup('The name of the design period. The standard naming convention is the experiment name, followed by the design iteration and start year. For example Broadbalk: 3rd Design Period (1968 - )'))
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
      ->setLabel(new TranslatableMarkup('Author'))
      ->setDescription(new TranslatableMarkup('The user ID of author of the experiment design.'))
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
      ->setLabel(new TranslatableMarkup('Authored on'))
      ->setDescription(new TranslatableMarkup('The time that the experiment design was created.'))
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
      ->setLabel(new TranslatableMarkup('Changed'))
      ->setDescription(new TranslatableMarkup('The time that the experiment design was last edited.'))
      ->setRevisionable(TRUE);

    $fields['experiment'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Related Experiment'))
      ->setDescription(new TranslatableMarkup('Please select the experiments that this design relates to.'))
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
      ->setLabel(new TranslatableMarkup('Status'))
      ->setDescription(new TranslatableMarkup('The status of the experiment design.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'requested' => new TranslatableMarkup('Requested'),
        'planning' => new TranslatableMarkup('Planning'),
        'active' => new TranslatableMarkup('Active'),
        'completed' => new TranslatableMarkup('Completed'),
        'cancelled' => new TranslatableMarkup('Cancelled'),
        'archived' => new TranslatableMarkup('Archived'),
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
      ->setLabel(new TranslatableMarkup('Status notes'))
      ->setDescription(new TranslatableMarkup('Any notes about the design status.'))
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
      ->setLabel(new TranslatableMarkup('Design description'))
      ->setDescription(new TranslatableMarkup('A description of the experiment design.'))
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
      ->setLabel(new TranslatableMarkup('Start year'))
      ->setDescription(new TranslatableMarkup('The start year of the experiment design.'))
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
      ->setLabel(new TranslatableMarkup('End year'))
      ->setDescription(new TranslatableMarkup('The end year of the experiment design.'))
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
      ->setLabel(new TranslatableMarkup('Changes from previous design'))
      ->setDescription(new TranslatableMarkup('Where relevant, please describe any changes from the previous statistical design and why the changes were made.'))
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
      ->setLabel(new TranslatableMarkup('Previous Cropping'))
      ->setDescription(new TranslatableMarkup('The crop(s) which were grown in the same location immediately before the experiment.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
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
      ->setLabel(new TranslatableMarkup('Rotation as Treatment'))
      ->setDescription(new TranslatableMarkup('Is the rotation a treatment in this experiment design? Rotations which are part of the treatment structure should be added via the plot attributes.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setDefaultValue(0)
      ->setSettings([
        'on_label' => new TranslatableMarkup('Yes'),
        'off_label' => new TranslatableMarkup('No'),
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
      ->setLabel(new TranslatableMarkup('Rotation name'))
      ->setDescription(new TranslatableMarkup('The name of the rotation.'))
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
      ->setLabel(new TranslatableMarkup('Rotation description'))
      ->setDescription(new TranslatableMarkup('A description of the rotation.'))
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
      ->setLabel(new TranslatableMarkup('Rotation Crops'))
      ->setDescription(new TranslatableMarkup('The crops in the rotation.'))
      ->setRevisionable(TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
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
      ->setLabel(new TranslatableMarkup('Rotation phasing'))
      ->setDescription(new TranslatableMarkup('The phasing of the rotation. E.g. winter wheat - winter oilseed rape - autumn cover crop - spring beans.'))
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
      ->setLabel(new TranslatableMarkup('Rotation notes'))
      ->setDescription(new TranslatableMarkup('Any additional notes about the rotation.'))
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
      ->setLabel(new TranslatableMarkup('Objective'))
      ->setDescription(new TranslatableMarkup('The objectives of the experiment design.'))
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
      ->setLabel(new TranslatableMarkup('Number of Treatment Factors'))
      ->setDescription(new TranslatableMarkup('The number of treatment factors being tested in the experiment, where a treatment factor is a variable under the control of the experimenter (sometimes also called explanatory variables) with two or more levels.'))
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
      ->setLabel(new TranslatableMarkup('Treatment Factors'))
      ->setDescription(new TranslatableMarkup('A description of the treatment factor(s) being tested in the experiment, with a list of the factor levels where applicable. Please add a new box for each treatment factor. For example: "Fungicide exposure (high, medium, low, none)" in one box and "Plant Breed Line (Cadenza, KWS Zyatt, KWS Extase) in another.'))
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
      ->setLabel(new TranslatableMarkup('Dependant Variables'))
      ->setDescription(new TranslatableMarkup('Describe the dependant variables, adding a new box for each variable. These are also called outcome or response variables, and are the measurement values that are being predicted (or their variation measured) by this experiment.'))
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
      ->setLabel(new TranslatableMarkup('Hypotheses'))
      ->setDescription(new TranslatableMarkup('The hypotheses that the design is testing. This must define your predictions. See https://scientific-publishing.webshop.elsevier.com/manuscript-preparation/what-how-write-good-hypothesis-research/'))
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
      ->setLabel(new TranslatableMarkup('Blocking Structure'))
      ->setDescription(new TranslatableMarkup('The blocking structure used for the experiment design.'))
      ->setRevisionable(TRUE)
      ->setSetting('allowed_values', [
        'crd' => new TranslatableMarkup('Completely Randomised Design'),
        'rcbd' => new TranslatableMarkup('Randomised Complete Block Design'),
        'rbd' => new TranslatableMarkup('Resolvable Block Design'),
        'nrbd' => new TranslatableMarkup('Non-resolvable Block Design'),
        'rrcd' => new TranslatableMarkup('Resolvable Row-Column Design'),
        'nrrcd' => new TranslatableMarkup('Non-resolvable Row-Column Design'),
        'spd' => new TranslatableMarkup('Split Plot Design'),
        'ad' => new TranslatableMarkup('Augmented Design'),
        'nr' => new TranslatableMarkup('Not Randomised'),
        'other' => new TranslatableMarkup('Other'),
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
      ->setLabel(new TranslatableMarkup('Statistical Design'))
      ->setDescription(new TranslatableMarkup('The statistical design associated with the experiment and blocking structure.'))
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
      ->setLabel(new TranslatableMarkup('Additional Blocking Constraints'))
      ->setDescription(new TranslatableMarkup('Any additional blocking constraints associated with the experiment design.'))
      ->setRequired(TRUE)
      ->setDefaultValue('none')
      ->setRevisionable(TRUE)
      ->setSetting('allowed_values', [
        't-latinization' => new TranslatableMarkup('T-Latinization'),
        'spatial_standards' => new TranslatableMarkup('Spatial Standards'),
        'spatial_design' => new TranslatableMarkup('Spatial Design'),
        'unequal_replication' => new TranslatableMarkup('Unequal Replication'),
        'other' => new TranslatableMarkup('Other (see description)'),
        'none' => new TranslatableMarkup('None'),
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
      ->setLabel(new TranslatableMarkup('Statistical Models'))
      ->setDescription(new TranslatableMarkup('The statistical model associated with the experiment.'))
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
      ->setLabel(new TranslatableMarkup('Number of Factor Level Combinations'))
      ->setDescription(new TranslatableMarkup('The number of unique treatments, where a unique treatment might be a combination of factor levels from two different treatment factors. For example, if you have two treatments factors, one for Fungicide Exposure with four factor levels (high, medium, low, none) and a second treatment factor for Wheat Variety with two factor levels (Variety 1 and Variety 2) and all four fungicide treatments are applied to each of the two varieties, then there are 8 factor level combinations (Variety 1 with high fungicide exposure, Variety 2 with high fungicide exposure, Variety 1 with medium fungicide exposure, Variety 2 with medium fungicide exposure, etc).'))
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
      ->setLabel(new TranslatableMarkup('Unequal Replication'))
      ->setDescription(new TranslatableMarkup('Please check if the experiment has unequal replication, in which case the replication strategy should be fully described in the Design Description.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setDefaultValue(0)
      ->setSettings([
        'on_label' => new TranslatableMarkup('Yes'),
        'off_label' => new TranslatableMarkup('No'),
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
      ->setLabel(new TranslatableMarkup('Number of Replicates'))
      ->setDescription(new TranslatableMarkup('The number of times each factor level combination is repeated in the experiment.'))
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
      ->setLabel(new TranslatableMarkup('Notes'))
      ->setDescription(new TranslatableMarkup('Any other additional notes relating to the design of the experiment.'))
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
      ->setLabel(new TranslatableMarkup('Layout description'))
      ->setDescription(new TranslatableMarkup('A description of the experiment layout.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'inline',
      ]);

    $fields['plot_non_standard'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Varying plot sizes'))
      ->setDescription(new TranslatableMarkup('Check if the plots vary in size across the experiment.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setDefaultValue(0)
      ->setSettings([
        'on_label' => new TranslatableMarkup('Yes'),
        'off_label' => new TranslatableMarkup('No'),
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
      'total_plot_area' => [
        'type' => 'float',
        'label' => new TranslatableMarkup('Total plot area'),
        'description' => new TranslatableMarkup('The total area covered by the plots.'),
        'suffix' => 'm2',
      ],
      'experiment_area' => [
        'type' => 'float',
        'label' => new TranslatableMarkup('Experiment area'),
        'description' => new TranslatableMarkup('The total area covered by the experiment.'),
        'suffix' => 'm2',
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
      ->setLabel(new TranslatableMarkup('Statisticians'))
      ->setDescription(new TranslatableMarkup('The statisticians responsible for the statistical design.'))
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
          'label' => new TranslatableMarkup('Crop Management Restrictions'),
          'description' => new TranslatableMarkup('Are there any restrictions that affect how the crop(s) in the experiment will be managed (cultivations, pesticide applications, etc?)'),
        ],
        'text' => [
          'label' => new TranslatableMarkup('Description of Crop Management Restrictions'),
          'description' => new TranslatableMarkup('Please describe the crop management restrictions. Note: All aspects of crop management will need to be discussed in detail with the trials team once the proposal has been approved.'),
        ],
      ],
      'restriction_gm' => [
        'boolean' => [
          'label' => new TranslatableMarkup('Genetically Modified (GM) Material'),
          'description' => new TranslatableMarkup('Is there any GM material being used?'),
        ],
        'text' => [
          'label' => new TranslatableMarkup('Description of GM material'),
          'description' => new TranslatableMarkup('Please describe the GM materials.'),
        ],
      ],
      'restriction_ge' => [
        'boolean' => [
          'label' => new TranslatableMarkup('Genetically Edited (GE) Material'),
          'description' => new TranslatableMarkup('Is there any GE material being used?'),
        ],
        'text' => [
          'label' => new TranslatableMarkup('Description of GE material'),
          'description' => new TranslatableMarkup('Please describe the GE materials.'),
        ],
      ],
      'restriction_off_label' => [
        'boolean' => [
          'label' => new TranslatableMarkup('Off-label Products'),
          'description' => new TranslatableMarkup('Is there a requirement for off-label or uncertified products (e.g. pesticides, growth regulators) to be applied?'),
        ],
        'text' => [
          'label' => new TranslatableMarkup('Description of off-label products'),
          'description' => new TranslatableMarkup('Please describe the off-label products.'),
        ],
      ],
      'restriction_licence_perm' => [
        'boolean' => [
          'label' => new TranslatableMarkup('Licence and Permissions'),
          'description' => new TranslatableMarkup('Do you need a specific licence or other permission?'),
        ],
        'text' => [
          'label' => new TranslatableMarkup('Licence and Permissions'),
          'description' => new TranslatableMarkup('Please describe the licence/permission restrictions.'),
        ],
      ],
      'restriction_physical' => [
        'boolean' => [
          'label' => new TranslatableMarkup('Physical Obstructions'),
          'description' => new TranslatableMarkup('Are there any physical obstructions in the field that will interfere with farm equipment and general management of the experiment?'),
        ],
        'text' => [
          'label' => 'Physical Obstructions',
          'description' => new TranslatableMarkup('Please describe the physical obstructions.'),
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
          'on_label' => new TranslatableMarkup('Yes'),
          'off_label' => new TranslatableMarkup('No'),
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
      ->setLabel(new TranslatableMarkup('Other restrictions'))
      ->setDescription(new TranslatableMarkup('If there are any other restrictions not covered above, please add them below'))
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
      ->setLabel(new TranslatableMarkup('Seed Provision'))
      ->setDescription(new TranslatableMarkup('Please state who will provide the seed.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setRevisionable(TRUE)
      ->setSetting('allowed_values', [
        'sponsor' => new TranslatableMarkup('Sponsor'),
        'farm' => new TranslatableMarkup('Farm'),
        'other' => new TranslatableMarkup('Other'),
        'na' => new TranslatableMarkup('Not applicable'),
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
        'label' => new TranslatableMarkup('Seed treatments'),
        'description' => new TranslatableMarkup('Please specify any requirements relating to seed treatments.'),
      ],
      'variety_notes' => [
        'label' => new TranslatableMarkup('Variety notes'),
        'description' => new TranslatableMarkup('Any other notes about the varieties requested/selected.'),
      ],
      'ploughing' => [
        'label' => new TranslatableMarkup('Ploughing'),
        'description' => new TranslatableMarkup('Detail any management related to ploughing.'),
      ],
      'levelling' => [
        'label' => new TranslatableMarkup('Levelling'),
        'description' => new TranslatableMarkup('Detail any management related to levelling.'),
      ],
      'seed_cultivation' => [
        'label' => new TranslatableMarkup('Seed bed cultivation'),
        'description' => new TranslatableMarkup('Detail any management related to seed bed cultivation.'),
      ],
      'planting_date' => [
        'label' => new TranslatableMarkup('Planting dates'),
        'description' => new TranslatableMarkup('Request specific planting dates.'),
      ],
      'seed_rate' => [
        'label' => new TranslatableMarkup('Seed rate'),
        'description' => new TranslatableMarkup('Request specific seed rates.'),
      ],
      'drilling_rate' => [
        'label' => new TranslatableMarkup('Drilling rate'),
        'description' => new TranslatableMarkup('Request specific drilling rates.'),
      ],
      'drill_spacing' => [
        'label' => new TranslatableMarkup('Drill spacing'),
        'description' => new TranslatableMarkup('Request specific drill spacing.'),
      ],
      'plant_estab' => [
        'label' => new TranslatableMarkup('Plant Establishment'),
        'description' => new TranslatableMarkup('Detail any management relating to plant establishment.'),
      ],
      'fungicide' => [
        'label' => new TranslatableMarkup('Fungicides'),
        'description' => new TranslatableMarkup('Please specify any requirements relating to fungicides and plant pathogen management.'),
      ],
      'herbicide' => [
        'label' => new TranslatableMarkup('Herbicides'),
        'description' => new TranslatableMarkup('Please specify any requirements relating to herbicides and weed management.'),
      ],
      'insecticide' => [
        'label' => new TranslatableMarkup('Insecticides'),
        'description' => new TranslatableMarkup('Please specify any requirements relating to insecticides and pest management.'),
      ],
      'nematicide' => [
        'label' => new TranslatableMarkup('Nematicides'),
        'description' => new TranslatableMarkup('Please specify any requirements relating to nematodes and nematicides.'),
      ],
      'molluscicide' => [
        'label' => new TranslatableMarkup('Molluscicides'),
        'description' => new TranslatableMarkup('Please specify any requirements relating to slugs, snails and molluscicide management.'),
      ],
      'pgr' => [
        'label' => new TranslatableMarkup('Plant growth regulators (PGR)'),
        'description' => new TranslatableMarkup('Please specify any requirements relating to lodging and plant growth regulators.'),
      ],
      'irrigation' => [
        'label' => new TranslatableMarkup('Irrigation'),
        'description' => new TranslatableMarkup('Please specify any requirements relating to irrigation.'),
      ],
      'organic_amendments' => [
        'label' => new TranslatableMarkup('Organic amendments'),
        'description' => new TranslatableMarkup('Request specific organic amendments (farmyard manure, poultry manure, compost, etc).'),
      ],
      'nitrogen' => [
        'label' => new TranslatableMarkup('Nitrogen (N)'),
        'description' => new TranslatableMarkup('Please specify any nitrogen management requests.'),
      ],
      'potassium' => [
        'label' => new TranslatableMarkup('Potassium (K)'),
        'description' => new TranslatableMarkup('Please specify any potassium management requests.'),
      ],
      'phosphorous' => [
        'label' => new TranslatableMarkup('Phosphorous (P)'),
        'description' => new TranslatableMarkup('Please specify any phosphorous management requests.'),
      ],
      'magnesium' => [
        'label' => new TranslatableMarkup('Magnesium (Mg)'),
        'description' => new TranslatableMarkup('Please specify any magnesium management requests.'),
      ],
      'sulphur' => [
        'label' => new TranslatableMarkup('Sulphur (S)'),
        'description' => new TranslatableMarkup('Please specify any sulphur management requests.'),
      ],
      'micronutrients' => [
        'label' => new TranslatableMarkup('Micronutrients'),
        'description' => new TranslatableMarkup('Please specify any micronutrient management requests.'),
      ],
      'ph' => [
        'label' => new TranslatableMarkup('Liming (pH)'),
        'description' => new TranslatableMarkup('Please specify any pH management requests.'),
      ],
      'grain_harvest' => [
        'label' => new TranslatableMarkup('Grain harvest'),
        'description' => new TranslatableMarkup('Please specify any grain harvest management.'),
      ],
      'straw_harvest' => [
        'label' => new TranslatableMarkup('Straw harvest'),
        'description' => new TranslatableMarkup('Please specify any straw harvest management.'),
      ],
      'other_harvest' => [
        'label' => new TranslatableMarkup('Other harvest'),
        'description' => new TranslatableMarkup('Please specify any other harvest management.'),
      ],
      'post_harvest' => [
        'label' => new TranslatableMarkup('Post-harvest management'),
        'description' => new TranslatableMarkup('Please specify any requirements for post-harvest management.'),
      ],
      'post_harvest_interval' => [
        'label' => new TranslatableMarkup('Post-harvest interval'),
        'description' => new TranslatableMarkup('Please specify a post-harvest interval if needed.'),
      ],
      'other' => [
        'label' => new TranslatableMarkup('Other'),
        'description' => new TranslatableMarkup('Any other issues relating to the experiment management.'),
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
      ->setLabel(new TranslatableMarkup('File'))
      ->setDescription(new TranslatableMarkup('Upload files associated with this design.'))
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
      ->setLabel(new TranslatableMarkup('Image'))
      ->setDescription(new TranslatableMarkup('Upload files associated with this design.'))
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
      ->setLabel(new TranslatableMarkup('Links'))
      ->setDescription(new TranslatableMarkup('Links to external website and documents associated with the design.'))
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

    // Add comment field.
    $fields['comment'] = FarmCommentHelper::commentBaseFieldDefinition('rothamsted_design');
    $fields['comment']->setDisplayOptions('form', [
      'type' => 'comment_default',
      'region' => 'hidden',
    ]);

    return $fields;
  }

}
