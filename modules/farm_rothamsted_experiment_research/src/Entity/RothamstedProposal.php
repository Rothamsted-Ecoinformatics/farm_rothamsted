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
use Drupal\farm_rothamsted_experiment_research\Form\DuplicateProposalForm;
use Drupal\farm_rothamsted_experiment_research\Form\EntityStatusChangeActionForm;
use Drupal\farm_rothamsted_experiment_research\Form\ProposalEntityForm;
use Drupal\farm_rothamsted_experiment_research\ResearchEntityPermissionProvider;
use Drupal\farm_rothamsted_experiment_research\RothamstedEntityListBuilder;
use Drupal\farm_rothamsted_experiment_research\Routing\EntityStatusChangeRouteProvider;
use Drupal\farm_ui_menu\Menu\DefaultSecondaryLocalTaskProvider;
use Drupal\link\LinkItemInterface;
use Drupal\user\EntityOwnerTrait;
use Drupal\user\UserInterface;

/**
 * Defines the research proposal entity class.
 */
#[ContentEntityType(
  id: 'rothamsted_proposal',
  label: new TranslatableMarkup('Proposal'),
  label_collection: new TranslatableMarkup('Proposals'),
  label_singular: new TranslatableMarkup('proposal'),
  label_plural: new TranslatableMarkup('proposals'),
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
      'add' => ProposalEntityForm::class,
      'edit' => ProposalEntityForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'duplicate' => DuplicateProposalForm::class,
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
    'canonical' => '/rothamsted/proposal/{rothamsted_proposal}',
    'collection' => '/rothamsted/proposal/all',
    'add-form' => '/rothamsted/proposal/add',
    'edit-form' => '/rothamsted/proposal/{rothamsted_proposal}/edit',
    'delete-form' => '/rothamsted/proposal/{rothamsted_proposal}/delete',
    'duplicate-form' => '/rothamsted/proposal/{rothamsted_proposal}/duplicate',
    'version-history' => '/rothamsted/proposal/{rothamsted_proposal}/revisions',
    'revision' => '/rothamsted/proposal/{rothamsted_proposal}/revisions/{rothamsted_proposal_revision}/view',
    'revision-revert-form' => '/rothamsted/proposal/{rothamsted_proposal}/revisions/{rothamsted_proposal_revision}/revert',
    'entity-status-action-form' => '/rothamsted/proposal/change-status',
  ],
  admin_permission: 'administer resarch proposals',
  base_table: 'rothamsted_proposal',
  data_table: 'rothamsted_proposal_data',
  revision_table: 'rothamsted_proposal_revision',
  translatable: TRUE,
  show_revision_ui: TRUE,
  revision_metadata_keys: [
    'revision_user' => 'revision_user',
    'revision_created' => 'revision_created',
    'revision_log_message' => 'revision_log_message',
  ],
)]
class RothamstedProposal extends RevisionableContentEntityBase implements RothamstedProposalInterface {

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
      ->setLabel(new TranslatableMarkup('Name'))
      ->setDescription(new TranslatableMarkup('The name of the proposal. If the experiment is already in FarmOS, please be consistent in how you name the proposal each year. For example "WGIN Diversity (2023)" should "WGIN Diversity (2024)" in the following cropping year.'))
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
      ->setDescription(new TranslatableMarkup('The user ID of author of the research proposal.'))
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
      ->setDescription(new TranslatableMarkup('The time that the research propsal was created.'))
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
      ->setDescription(new TranslatableMarkup('The time that the research proposal was last edited.'))
      ->setRevisionable(TRUE);

    $fields['study_id'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Study ID'))
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setSetting('text_processing', 0)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -15,
      ]);

    $fields['program'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Research Programs'))
      ->setDescription(new TranslatableMarkup('The research program which this proposal is part of.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'rothamsted_program')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -15,
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
        'weight' => -15,
      ]);

    $fields['experiment'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Related Experiments'))
      ->setDescription(new TranslatableMarkup('The experiment(s) relating to this proposal. If this is the second or subsequent year of an experiment that has already been added to FarmOS, please select it here before submitting the proposal. If this is the first year of the experiment, leave this blank and add it after the proposal is approved.'))
      ->setRevisionable(TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'rothamsted_experiment')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -15,
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
        'weight' => -15,
      ]);

    $fields['design'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Related Designs'))
      ->setDescription(new TranslatableMarkup('The experiment design relating to this proposal. If this design has already been added to FarmOS, please select it here before submitting the proposal. If this is the first year of the experiment, or if you wish to change the design from previous years, a new design will have to added after the proposal is approved.'))
      ->setRevisionable(TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'rothamsted_design')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -15,
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
        'weight' => -15,
      ]);

    $fields['plan'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Related Study Plans'))
      ->setDescription(new TranslatableMarkup('The study plan relating to this proposal. If this plan has already been added to FarmOS, please select it here before submitting the proposal.'))
      ->setRevisionable(TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'plan')
      ->setSetting('handler', 'default:plan')
      ->setSetting('handler_settings', [
        'target_bundles' => [
          'rothamsted_experiment' => 'rothamsted_experiment',
        ],
        'sort' => [
          'field' => '_none',
        ],
        'auto_create' => FALSE,
        'auto_create_bundle' => '',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -15,
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
        'weight' => -15,
      ]);

    $fields['contact'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Contacts'))
      ->setDescription(new TranslatableMarkup('List researchers that are contacts for this proposal.'))
      ->setRequired(TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'rothamsted_researcher')
      ->setSetting('handler', 'views')
      ->setSetting('handler_settings', [
        'view' => [
          'view_name' => 'rothamsted_researcher_reference',
          'display_name' => 'entity_reference',
          'arguments' => [],
        ],
      ])
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

    $fields['statistician'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Statistician'))
      ->setDescription(new TranslatableMarkup('Please select the statistician associated with this proposal.'))
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
      ])
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

    $fields['data_steward'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Data Steward'))
      ->setDescription(new TranslatableMarkup('Please select the data steward associated with this proposal.'))
      ->setRequired(TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'rothamsted_researcher')
      ->setSetting('handler', 'views')
      ->setSetting('handler_settings', [
        'view' => [
          'view_name' => 'rothamsted_researcher_reference',
          'display_name' => 'entity_reference',
          'arguments' => [
            'role' => 'data_curator',
          ],
        ],
      ])
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

    $fields['experiment_category'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Experiment category'))
      ->setDescription(new TranslatableMarkup('The experiment category.'))
      ->setRevisionable(TRUE)
      ->setSetting('allowed_values_function', 'farm_rothamsted_experiment_research_experiment_category_field_allowed_values')
      ->addConstraint('RothamstedStatus', ['requiredStatuses' => ['approved', 'archived']])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'list_default',
        'label' => 'inline',
      ]);

    $fields['research_question'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Research questions'))
      ->setDescription(new TranslatableMarkup('The research question you expect to answer with the experiment, and how it relates to the research program.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setRevisionable(TRUE)
      ->addConstraint('RothamstedStatus', ['requiredStatuses' => ['submitted', 'approved', 'rejected', 'archived']])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'inline',
      ]);

    // Integer year fields.
    $fields['planting_year'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Planting Year'))
      ->setDescription(new TranslatableMarkup('The planting year for the study.'))
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
    $fields['harvest_year'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Harvest Year'))
      ->setDescription(new TranslatableMarkup('The year the experiment is to be harvested.'))
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

    $fields['crop'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Crops'))
      ->setDescription(new TranslatableMarkup('The crops being proposed for study.'))
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
      ->addConstraint('RothamstedStatus', ['requiredStatuses' => ['approved', 'rejected', 'archived']])
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

    $fields['previous_cropping'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Previous Cropping'))
      ->setDescription(new TranslatableMarkup('If necessary, you can request that your experiment is planted in a field currently planted with a specific crop. You can also add multiple options. For example, if you are studying second wheat, you can request that the experiment is drilled in a field which is currently planted with winter wheat or spring wheat. Requests will be accommodated where possible.'))
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

    $fields['num_treatments'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Number of Treatment Factors'))
      ->setDescription(new TranslatableMarkup('The number of treatment factors being tested in the experiment, where a treatment factor is a variable under the control of the experimenter (sometimes also called explanatory variables) with two or more levels.'))
      ->setRevisionable(TRUE)
      ->setSetting('min', 0)
      ->addConstraint('RothamstedStatus', ['requiredStatuses' => ['submitted', 'approved', 'rejected', 'archived']])
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
      ->setLabel(new TranslatableMarkup('Treatment factors'))
      ->setDescription(new TranslatableMarkup('A description of the treatment factor(s) being tested in the experiment, with a list of the factor levels where applicable. Please add a new box for each treatment factor. For example: "Fungicide exposure (high, medium, low, none)" in one box and "Plant Breed Line (Cadenza, KWS Zyatt, KWS Extase) in another.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setRevisionable(TRUE)
      ->addConstraint('RothamstedStatus', ['requiredStatuses' => ['submitted', 'approved', 'rejected', 'archived']])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'inline',
      ]);

    $fields['num_replicates'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Number of Replicates'))
      ->setDescription(new TranslatableMarkup('The number of times each factor level combination is repeated in the experiment.'))
      ->setRevisionable(TRUE)
      ->setSetting('min', 0)
      ->addConstraint('RothamstedStatus', ['requiredStatuses' => ['submitted', 'approved', 'rejected', 'archived']])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'number',
        'label' => 'inline',
      ]);

    $fields['num_plots_total'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Total number of plots'))
      ->setDescription(new TranslatableMarkup('The total number of plots being proposed.'))
      ->setRevisionable(TRUE)
      ->setSetting('min', 0)
      ->addConstraint('RothamstedStatus', ['requiredStatuses' => ['submitted', 'approved', 'rejected', 'archived']])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'number',
        'label' => 'inline',
      ]);

    $fields['statistical_design'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Statistical Design'))
      ->setDescription(new TranslatableMarkup('Describe the statistical design associated with the proposal.'))
      ->setRevisionable(TRUE)
      ->addConstraint('RothamstedStatus', ['requiredStatuses' => ['submitted', 'approved', 'rejected', 'archived']])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'label' => 'inline',
      ]);

    $fields['measurements'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Measurements'))
      ->setDescription(new TranslatableMarkup('Describe the measurements you propose to take, approximate dates and who is responsible for taking the measurements. This should include measurements to be taken by the farm (yields, etc), measurements to be taken by the Sponsor, and measurements which will be taken by external consultants.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setRevisionable(TRUE)
      ->addConstraint('RothamstedStatus', ['requiredStatuses' => ['submitted', 'approved', 'rejected', 'archived']])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'inline',
      ]);

    $fields['requested_location'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Requested Field Location'))
      ->setDescription(new TranslatableMarkup('If you have any specific location(s) where you would like to site the experiment, please include them here. PLEASE NOTE THAT THIS AT THE FARMS DISCRETION AND NOT GUARANTEED.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'asset')
      ->setSetting('handler', 'views')
      ->setSetting('handler_settings', [
        'view' => [
          'view_name' => 'farm_location_reference',
          'display_name' => 'entity_reference',
          'arguments' => [],
        ],
      ])
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

    $fields['unsuitable_location'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Unsuitable Field Location'))
      ->setDescription(new TranslatableMarkup('Please select any field locations which are not suitable for this proposal'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'asset')
      ->setSetting('handler', 'views')
      ->setSetting('handler_settings', [
        'view' => [
          'view_name' => 'farm_location_reference',
          'display_name' => 'entity_reference',
          'arguments' => [],
        ],
      ])
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

    $fields['field_layout'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('In-Field layout'))
      ->setDescription(new TranslatableMarkup('Please describe how you would propose to lay the experiment out in the field (guard rows, row spacing, number of plots per row, etc) and any limitations that would affect where the experiment can be situated.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'inline',
      ]);

    $fields['plot_length'] = BaseFieldDefinition::create('float')
      ->setLabel(new TranslatableMarkup('Plot length'))
      ->setDescription(new TranslatableMarkup('The proposed plot length.'))
      ->setRevisionable(TRUE)
      ->setSetting('min', 0)
      ->setSetting('suffix', 'm')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'number',
        'label' => 'inline',
      ]);

    $fields['plot_width'] = BaseFieldDefinition::create('float')
      ->setLabel(new TranslatableMarkup('Plot width'))
      ->setDescription(new TranslatableMarkup('The proposed plot width.'))
      ->setRevisionable(TRUE)
      ->setSetting('min', 0)
      ->setSetting('suffix', 'm')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'number',
        'label' => 'inline',
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
          'description' => new TranslatableMarkup('Does the proposal include any genetically modified (GM) material?'),
        ],
        'text' => [
          'label' => new TranslatableMarkup('Description of GM material'),
          'description' => new TranslatableMarkup('Please describe the GM materials.'),
        ],
      ],
      'restriction_ge' => [
        'boolean' => [
          'label' => new TranslatableMarkup('Genetically Edited (GE) Material'),
          'description' => new TranslatableMarkup('Does the proposal include any genetically edited (GE) material?'),
        ],
        'text' => [
          'label' => new TranslatableMarkup('Description of GE material'),
          'description' => new TranslatableMarkup('Please describe the GE materials.'),
        ],
      ],
      'restriction_off_label' => [
        'boolean' => [
          'label' => new TranslatableMarkup('Off-label Products'),
          'description' => new TranslatableMarkup('Does this proposal require the use of off-label or uncertified products (e.g. pesticides, growth regulators)?'),
        ],
        'text' => [
          'label' => new TranslatableMarkup('Description of off-label products'),
          'description' => new TranslatableMarkup('Please describe the off-label products.'),
        ],
      ],
      'restriction_licence_perm' => [
        'boolean' => [
          'label' => new TranslatableMarkup('Licence and Permissions'),
          'description' => new TranslatableMarkup('Does the proposal require any other specialist licences or permissions?'),
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

    // Other restrictions.
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

    // Management fields.
    $fields['experiment_management'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Experiment management'))
      ->setDescription(new TranslatableMarkup('The management strategy for the associated experiment.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'inline',
      ]);

    $management_options = [
      'farm' => new TranslatableMarkup('Farm'),
      'sponsor' => new TranslatableMarkup('Sponsor'),
      'other' => new TranslatableMarkup('Other'),
    ];
    $management_fields = [
      'management_seed_supply' => [
        'label' => new TranslatableMarkup('Seed Supply'),
        'description' => new TranslatableMarkup('Who will supply the seed for this experiment. Please select multiple if this is a shared responsibility.'),
        'options' => $management_options,
      ],
      'management_seed_treatment' => [
        'label' => new TranslatableMarkup('Seed Treatment'),
        'description' => new TranslatableMarkup('If the seed needs to be treated, please state who is responsible for this. Please select multiple if this is a shared responsibility.'),
        'options' => $management_options + ['supplier' => new TranslatableMarkup('Supplier')],
      ],
      'management_pesticide' => [
        'label' => new TranslatableMarkup('Pesticide Applications'),
        'description' => new TranslatableMarkup('Who is responsible for the pesticide applications? Please select multiple if this is a shared responsibility.'),
        'options' => $management_options,
      ],
      'management_nutrition' => [
        'label' => new TranslatableMarkup('Nutrition Applications'),
        'description' => new TranslatableMarkup('Who is responsible for the nutrient applications? Please select multiple if this is a shared responsibility."'),
        'options' => $management_options,
      ],
      'management_harvest' => [
        'label' => new TranslatableMarkup('Harvest'),
        'description' => new TranslatableMarkup('Who is responsible for harvesting the experiment? Please select multiple if this is a shared responsibility.'),
        'options' => $management_options,
      ],
    ];
    foreach ($management_fields as $field_id => $field_info) {
      $fields[$field_id] = BaseFieldDefinition::create('list_string')
        ->setLabel($field_info['label'])
        ->setDescription($field_info['description'])
        ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
        ->setRevisionable(TRUE)
        ->setSetting('allowed_values', $field_info['options'])
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
    }

    // Common file field settings.
    $file_settings = [
      'file_directory' => 'rothamsted/rothamsted_proposal/[date:custom:Y]-[date:custom:m]',
      'max_filesize' => '',
      'handler' => 'default:file',
      'handler_settings' => [],
    ];
    $file_field_settings = $file_settings + [
      'description_field' => TRUE,
      'file_extensions' => 'csv doc docx gz geojson gpx kml kmz logz mp3 odp ods odt ogg pdf ppt pptx tar tif tiff txt wav xls xlsx zip',
    ];

    $fields['initial_quote'] = BaseFieldDefinition::create('file')
      ->setLabel(new TranslatableMarkup('Initial Quote'))
      ->setDescription(new TranslatableMarkup('Preliminary quotations for the work proposed.'))
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

    $fields['file'] = BaseFieldDefinition::create('file')
      ->setLabel(new TranslatableMarkup('File'))
      ->setDescription(new TranslatableMarkup('Upload files associated with this proposal.'))
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
      ->setDescription(new TranslatableMarkup('Upload files associated with this proposal.'))
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
      ->setDescription(new TranslatableMarkup('Links to external website and documents associated with the proposal.'))
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

    $fields['reviewer'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Reviewers'))
      ->setDescription(new TranslatableMarkup('The researchers who have reviewed this proposal.'))
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
        'weight' => -15,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
      ]);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Status'))
      ->setDescription(new TranslatableMarkup('The status of the proposal.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'draft' => new TranslatableMarkup('Draft'),
        'submitted' => new TranslatableMarkup('Submitted'),
        'approved' => new TranslatableMarkup('Approved'),
        'rejected' => new TranslatableMarkup('Rejected'),
        'planning' => new TranslatableMarkup('Planning'),
        'completed' => new TranslatableMarkup('Completed'),
        'cancelled' => new TranslatableMarkup('Cancelled'),
        'archived' => new TranslatableMarkup('Archived'),
      ])
      ->setDefaultValue('draft')
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
      ->setDescription(new TranslatableMarkup('Any notes about the proposal status.'))
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

    // Add comment field.
    $fields['comment'] = FarmCommentHelper::commentBaseFieldDefinition('rothamsted_proposal');
    $fields['comment']->setDisplayOptions('form', [
      'type' => 'comment_default',
      'region' => 'hidden',
    ]);

    return $fields;
  }

}
