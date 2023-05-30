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
 * Defines the research proposal entity class.
 *
 * @ContentEntityType(
 *   id = "rothamsted_proposal",
 *   label = @Translation("Proposal"),
 *   label_collection = @Translation("Proposals"),
 *   label_singular = @Translation("proposal"),
 *   label_plural = @Translation("proposals"),
 *   handlers = {
 *     "access" = "\Drupal\entity\UncacheableEntityAccessControlHandler",
 *     "list_builder" = "Drupal\farm_rothamsted_experiment_research\RothamstedEntityListBuilder",
 *     "permission_provider" = "\Drupal\entity\UncacheableEntityPermissionProvider",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\entity\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\farm_rothamsted_experiment_research\Form\ProposalEntityForm",
 *       "edit" = "Drupal\farm_rothamsted_experiment_research\Form\ProposalEntityForm",
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
 *   base_table = "rothamsted_proposal",
 *   data_table = "rothamsted_proposal_data",
 *   revision_table = "rothamsted_proposal_revision",
 *   translatable = TRUE,
 *   revisionable = TRUE,
 *   show_revision_ui = TRUE,
 *   admin_permission = "administer resarch proposals",
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
 *     "canonical" = "/rothamsted/proposal/{rothamsted_proposal}",
 *     "collection" = "/rothamsted/proposal",
 *     "add-form" = "/rothamsted/proposal/add",
 *     "edit-form" = "/rothamsted/proposal/{rothamsted_proposal}/edit",
 *     "delete-form" = "/rothamsted/proposal/{rothamsted_proposal}/delete",
 *     "version-history" = "/rothamsted/proposal/{rothamsted_proposal}/revisions",
 *     "revision" = "/rothamsted/proposal/{rothamsted_proposal}/revisions/{rothamsted_proposal_revision}/view",
 *     "revision-revert-form" = "/rothamsted/proposal/{rothamsted_proposal}/revisions/{rothamsted_proposal_revision}/revert",
 *   }
 * )
 */
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
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the proposal. If the experiment is already in FarmOS, please be consistent in how you name the proposal each year. For example "WGIN Diversity (2023)" should "WGIN Diversity (2024)" in the following cropping year.'))
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
      ->setDescription(t('The user ID of author of the research proposal.'))
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
      ->setDescription(t('The time that the research propsal was created.'))
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
      ->setDescription(t('The time that the research proposal was last edited.'))
      ->setRevisionable(TRUE);

    $fields['program'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Research Programs'))
      ->setDescription(t('The research program which this proposal is part of.'))
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
      ->setLabel(t('Related Experiments'))
      ->setDescription(t('The experiment(s) relating to this proposal. If this is the second or subsequent year of an experiment that has already been added to FarmOS, please select it here before submitting the proposal. If this is the first year of the experiment, leave this blank and add it after the proposal is approved.'))
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
      ->setLabel(t('Related Designs'))
      ->setDescription(t('The experiment design relating to this proposal. If this design has already been added to FarmOS, please select it here before submitting the proposal. If this is the first year of the experiment, or if you wish to change the design from previous years, a new design will have to added after the proposal is approved.'))
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
      ->setLabel(t('Related Experiment Plans'))
      ->setDescription(t('The experiment plan relating to this proposal. If this plan has already been added to FarmOS, please select it here before submitting the proposal.'))
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
      ->setLabel(t('Contacts'))
      ->setDescription(t('List researchers that are contacts for this proposal.'))
      ->setRequired(TRUE)
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

    $fields['experiment_category'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Experiment category'))
      ->setDescription(t('The experiment category.'))
      ->setRevisionable(TRUE)
      ->setSetting('allowed_values', [
        'reserve_site' => t('Reserve Site'),
        'annual_crop_experiment' => t('Annual Crop Experiment'),
        'crop_sequence_experiment' => t('Crop Sequence Experiment'),
        'classical_experiment' => t('Classical Experiment'),
        'energy_crop_experiment' => t('Energy Crop Experiment'),
        'longterm_experiment' => t('Longterm Experiment'),
        'other' => t('Other'),
      ])
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
      ->setLabel(t('Research questions'))
      ->setDescription(t('The research question you expect to answer with the experiment, and how it relates to the research program.'))
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

    $fields['amendments'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Proposed Amendments'))
      ->setDescription(t('A description of any proposed changes made to the experiment or experiment design since the last study period. Amendments required before the proposal can be approved'))
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

    $fields['crop'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Crops'))
      ->setDescription(t('The crops being proposed for study.'))
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

    $fields['num_treatments'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Number of Treatment Factors'))
      ->setDescription(t('The proposed number of treatment factors.'))
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
      ->setLabel(t('Treatment factors'))
      ->setDescription(t('A description of the proposed treatment factors and factor levels.'))
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
      ->setLabel(t('Number of Replicates'))
      ->setDescription(t('The proposed number of replicates for each factor level combination.'))
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
      ->setLabel(t('Total number of plots'))
      ->setDescription(t('The total number of plots being proposed.'))
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
      ->setLabel(t('Statistical Design'))
      ->setDescription(t('Describe the statistical design associated with the proposal.'))
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
      ->setLabel(t('Measurements'))
      ->setDescription(t('Describe the measurements you propose to take, approximate dates and who is responsible for taking the measurements. This should include measurements to be taken by the farm (yields, etc), measurements to be taken by the Sponsor, and measurements which will be taken by external consultants.'))
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
      ->setLabel(t('Requested Field Location'))
      ->setDescription(t('If you have any specific location(s) where you would like to site the experiment, please include them here.'))
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
      ->setLabel(t('In-Field layout'))
      ->setDescription(t('Please describe how you would propose to lay the experiment out in the field (guard rows, row spacing, number of plots per row, etc) and any limitations that would affect where the experiment can be situated.'))
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
      ->setLabel(t('Plot length'))
      ->setDescription(t('The proposed plot length.'))
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
      ->setLabel(t('Plot width'))
      ->setDescription(t('The proposed plot width.'))
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
          'description' => t('Does the proposal include any genetically modified (GM) material?'),
        ],
        'text' => [
          'label' => t('Description of GM material'),
          'description' => t('Please describe the GM materials.'),
        ],
      ],
      'restriction_ge' => [
        'boolean' => [
          'label' => t('Genetically Edited (GE) Material'),
          'description' => t('Does the proposal include any genetically edited (GE) material?'),
        ],
        'text' => [
          'label' => t('Description of GE material'),
          'description' => t('Please describe the GE materials.'),
        ],
      ],
      'restriction_off_label' => [
        'boolean' => [
          'label' => t('Off-label Products'),
          'description' => t('Does this proposal require the use of off-label or uncertified products (e.g. pesticides, growth regulators)?'),
        ],
        'text' => [
          'label' => t('Description of off-label products'),
          'description' => t('Please describe the off-label products.'),
        ],
      ],
      'restriction_licence_perm' => [
        'boolean' => [
          'label' => t('Licence and Permissions'),
          'description' => t('Does the proposal require any other specialist licences or permissions?'),
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

    // Other restrictions.
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

    // Management fields.
    $fields['experiment_management'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Experiment management'))
      ->setDescription(t('The management strategy for the associated experiment.'))
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
      'farm' => t('Farm'),
      'sponsor' => t('Sponsor'),
      'other' => t('Other'),
    ];
    $management_fields = [
      'management_seed_supply' => [
        'label' => t('Seed Supply'),
        'description' => t('Who will supply the seed for this experiment. Please select multiple if this is a shared responsibility.'),
        'options' => $management_options,
      ],
      'management_seed_treatment' => [
        'label' => t('Seed Treatment'),
        'description' => t('If the seed needs to be treated, please state who is responsible for this. Please select multiple if this is a shared responsibility.'),
        'options' => $management_options + ['supplier' => t('Supplier')],
      ],
      'management_pesticide' => [
        'label' => t('Pesticide Applications'),
        'description' => t('Who is responsible for the pesticide applications? Please select multiple if this is a shared responsibility.'),
        'options' => $management_options,
      ],
      'management_nutrition' => [
        'label' => t('Nutrition Applications'),
        'description' => t('Who is responsible for the nutrient applications? Please select multiple if this is a shared responsibility."'),
        'options' => $management_options,
      ],
      'management_harvest' => [
        'label' => t('Harvest'),
        'description' => t('Who is responsible for harvesting the experiment? Please select multiple if this is a shared responsibility.'),
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
      ->setLabel(t('Initial Quote'))
      ->setDescription(t('Preliminary quotations for the work proposed.'))
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
      ->setLabel(t('File'))
      ->setDescription(t('Upload files associated with this proposal.'))
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
      ->setDescription(t('Upload files associated with this proposal.'))
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
      ->setDescription(t('Links to external website and documents associated with the proposal.'))
      ->setRevisionable(TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSettings([
        'title' => DRUPAL_DISABLED,
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
      ->setLabel(t('Reviewers'))
      ->setDescription(t('The researchers who have reviewed this proposal.'))
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

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setDescription(t('The status of the proposal.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'draft' => t('Draft'),
        'submitted' => t('Submitted'),
        'approved' => t('Approved'),
        'rejected' => t('Rejected'),
        'archived' => t('Archived'),
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
      ->setLabel(t('Status notes'))
      ->setDescription(t('Any notes about the proposal status.'))
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
    $fields['comment'] = farm_rothamsted_experiment_research_comment_base_field_definition('rothamsted_proposal');

    return $fields;
  }

}
