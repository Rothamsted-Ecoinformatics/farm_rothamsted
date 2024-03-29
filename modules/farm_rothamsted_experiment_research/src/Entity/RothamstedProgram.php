<?php

namespace Drupal\farm_rothamsted_experiment_research\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\RevisionLogEntityTrait;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\user\EntityOwnerTrait;
use Drupal\user\UserInterface;

/**
 * Defines the research program entity class.
 *
 * @ContentEntityType(
 *   id = "rothamsted_program",
 *   label = @Translation("Research Program"),
 *   label_collection = @Translation("Research Programs"),
 *   label_singular = @Translation("research program"),
 *   label_plural = @Translation("research programs"),
 *   handlers = {
 *     "access" = "\Drupal\entity\UncacheableEntityAccessControlHandler",
 *     "list_builder" = "Drupal\farm_rothamsted_experiment_research\RothamstedEntityListBuilder",
 *     "permission_provider" = "Drupal\farm_rothamsted_experiment_research\ResearchEntityPermissionProvider",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\entity\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\farm_rothamsted_experiment_research\Form\ResearchEntityForm",
 *       "edit" = "Drupal\farm_rothamsted_experiment_research\Form\ResearchEntityForm",
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
 *   base_table = "rothamsted_program",
 *   data_table = "rothamsted_program_data",
 *   revision_table = "rothamsted_program_revision",
 *   translatable = TRUE,
 *   revisionable = TRUE,
 *   show_revision_ui = TRUE,
 *   admin_permission = "administer resarch programs",
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
 *     "canonical" = "/rothamsted/program/{rothamsted_program}",
 *     "collection" = "/rothamsted/program",
 *     "add-form" = "/rothamsted/program/add",
 *     "edit-form" = "/rothamsted/program/{rothamsted_program}/edit",
 *     "delete-form" = "/rothamsted/program/{rothamsted_program}/delete",
 *     "version-history" = "/rothamsted/program/{rothamsted_program}/revisions",
 *     "revision" = "/rothamsted/program/{rothamsted_program}/revisions/{rothamsted_program_revision}/view",
 *     "revision-revert-form" = "/rothamsted/program/{rothamsted_program}/revisions/{rothamsted_program_revision}/revert",
 *   }
 * )
 */
class RothamstedProgram extends RevisionableContentEntityBase implements RothamstedProgramInterface {

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
      ->setDescription(t('The name of the research program.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setSetting('text_processing', 0)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setDescription(t('The user ID of author of the research program.'))
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
      ->setDescription(t('The time that the research program was created.'))
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
      ->setDescription(t('The time that the research program was last edited.'))
      ->setRevisionable(TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setDescription(t('The status of the program.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'requested' => t('Requested'),
        'active' => t('Active'),
        'completed' => t('Completed'),
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

    $fields['project_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Project code'))
      ->setDescription(t('The project code assigned to the Research Programme.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
        'settings' => [
          'size' => 25,
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'label' => 'inline',
        'weight' => -10,
      ]);

    $fields['abbreviation'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Abbreviation'))
      ->setDescription(t('The abbreviated name of the Research Programme.'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
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

    $fields['principal_investigator'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Principal Investigators'))
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

    $fields['funder'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Funders'))
      ->setDescription(t('The name of the organisation funding the Research Program.'))
      ->setRevisionable(TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('allowed_values', [
        'ADAS' => 'ADAS',
        'AHDB' => 'AHDB',
        'AHRC' => 'AHRC',
        'BASF' => 'BASF',
        'bayer' => 'Bayer',
        'BBSRC' => 'BBSRC',
        'british_council' => 'British Council',
        'BEIS' => 'BEIS',
        'CIMMYT' => 'CIMMYT',
        'defra' => 'Defra',
        'chadacre_agricultural_trust' => 'Chadacre Agricultural Trust',
        'CHAP' => 'CHAP',
        'environment_agency' => 'Environment Agency',
        'EPSRC' => 'EPSRC',
        'european_commission' => 'European Commission',
        'esa' => 'European Space Agency (ESA)',
        'fcdo' => 'Foreign Commonwealth And Development Office',
        'ffar' => 'Foundation for Food and Agricultural Research',
        'general_mills' => 'General Mills',
        'innovate_uk' => 'Innovate UK',
        'international_potato_centre' => 'International Potato Centre',
        'ireland_epa' => 'Ireland EPA',
        'KTN' => 'KTN',
        'met_office' => 'Met Office',
        'natural_england' => 'Natural England',
        'NERC' => 'NERC',
        'norwegian_research_council' => 'Norwegian Research Council',
        'novo_nordisk_foundation' => 'Novo Nordisk Foundation',
        'novozyme' => 'Novozyme',
        'lawes_agricultural_trust' => 'Lawes Agricultural Trust',
        'syngenta' => 'Syngenta',
        'the_royal_society' => 'The Royal Society',
        'the_alan_turing_institute' => 'The Alan Turing Institute',
        'UKRI' => 'UKRI',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'list_default',
        'label' => 'inline',
      ]);

    $fields['grant_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Funder Grant codes'))
      ->setDescription(t('The code assigned to the Research Program by the funder.'))
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

    $fields['start'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Start date'))
      ->setDescription(t('The start date of the program.'))
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

    $fields['end'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('End date'))
      ->setDescription(t('The end date of the program.'))
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

    $fields['notes'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Notes'))
      ->setDescription(t('Notes about the research program.'))
      ->setRevisionable(TRUE)
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
