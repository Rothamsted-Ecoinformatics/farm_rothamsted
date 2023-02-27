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
 * Defines the research experiment entity class.
 *
 * @ContentEntityType(
 *   id = "rothamsted_experiment",
 *   label = @Translation("Experiment"),
 *   label_collection = @Translation("Experiments"),
 *   label_singular = @Translation("experiment"),
 *   label_plural = @Translation("experiments"),
 *   handlers = {
 *     "access" = "\Drupal\entity\UncacheableEntityAccessControlHandler",
 *     "list_builder" = "Drupal\farm_rothamsted_experiment_research\RothamstedEntityListBuilder",
 *     "permission_provider" = "\Drupal\entity\UncacheableEntityPermissionProvider",
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
 *       "default" = "\Drupal\farm_rothamsted_experiment_research\Menu\DefaultSecondaryTaskProvider",
 *     },
 *   },
 *   base_table = "rothamsted_experiment",
 *   data_table = "rothamsted_experiment_data",
 *   revision_table = "rothamsted_experiment_revision",
 *   translatable = TRUE,
 *   revisionable = TRUE,
 *   show_revision_ui = TRUE,
 *   admin_permission = "administer resarch experiments",
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
 *     "canonical" = "/rothamsted/experiment/{rothamsted_experiment}",
 *     "collection" = "/rothamsted/experiment",
 *     "add-form" = "/rothamsted/experiment/add",
 *     "edit-form" = "/rothamsted/experiment/{rothamsted_experiment}/edit",
 *     "delete-form" = "/rothamsted/experiment/{rothamsted_experiment}/delete",
 *     "version-history" = "/rothamsted/experiment/{rothamsted_experiment}/revisions",
 *     "revision" = "/rothamsted/experiment/{rothamsted_experiment}/revisions/{rothamsted_experiment_revision}/view",
 *     "revision-revert-form" = "/rothamsted/experiment/{rothamsted_experiment}/revisions/{rothamsted_experiment_revision}/revert",
 *   }
 * )
 */
class RothamstedExperiment extends RevisionableContentEntityBase implements RothamstedExperimentInterface {

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
      ->setDescription(t('The user ID of author of the research experiment.'))
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
      ->setDescription(t('The time that the research experiment was created.'))
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
      ->setDescription(t('The time that the research experiment was last edited.'))
      ->setRevisionable(TRUE);

    $fields['program'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Related Programs'))
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

    $fields['code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Experiment code'))
      ->setDescription(t('The experiment code.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
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
      ->setDescription(t('The abbreviated name of the experiment.'))
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
      ->setLabel(t('Description'))
      ->setDescription(t('A description of the experiment.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'inline',
      ]);

    $fields['category'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Category'))
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
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'list_default',
        'label' => 'inline',
      ]);

    $fields['start'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Start date'))
      ->setDescription(t('The start date of the experiment.'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp_optional',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'timestamp',
        'label' => 'inline',
        'settings' => [
          'date_format' => 'html_date',
          'custom_date_format' => '',
          'timezone' => '',
        ],
      ]);

    $fields['end'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('End date'))
      ->setDescription(t('The end date of the experiment.'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp_optional',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'timestamp',
        'label' => 'inline',
        'settings' => [
          'date_format' => 'html_date',
          'custom_date_format' => '',
          'timezone' => '',
        ],
      ]);

    $fields['website'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Website'))
      ->setDescription(t('The URL for the experiment website.'))
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

    $fields['objective'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Objective'))
      ->setDescription(t('The objectives of the experiment.'))
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

    $fields['rotation_treatment'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Rotation as Treatment'))
      ->setDescription(t('Is the rotation a treatment in this experiment? Rotations which are part of the treatment structure should be added via the plot attributes.'))
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

    $fields['researcher'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Researchers'))
      ->setDescription(t('Researchers that are associated with this experiment.'))
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

    $fields['confidential_treatment'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Confidential treatments'))
      ->setDescription(t('Are the treatments being applied in this experiment confidential?'))
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

    $fields['data_license'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Data license'))
      ->setDescription(t('The license associated with the experiment data.'))
      ->setRevisionable(TRUE)
      ->setSetting('allowed_options', [
        'public_domain' => t('Public Domain'),
        'cc0' => t('CC0 (No Rights Reserved, Public Domain'),
        'pddl' => t('PDDL (Open Data Commons Public Domain Dedication and License)'),
        'cc-by' => t('CC-BY (Attribution)'),
        'cdla-permissive' => t('CDLA-Permissive (Community Data License Agreement – Permissive)'),
        'odc-by' => t('ODC-BY (Open Data Commons Attribution License)'),
        'cc-by-sa' => t('CC BY-SA (Attribution-ShareAlike)'),
        'cdla-sharing' => t('CDLA-Sharing (Community Data License Agreement)'),
        'odc-odbl' => t('ODC-ODbL (Open Data Commons Open Database License)'),
        'cc-by-nc' => t('CC BY-NC (Attribution-NonCommercial)'),
        'cc-by-nd' => t('CC BY-ND (Attribution-NoDerivatives)'),
        'cc-by-nc-sa' => t('CC BY-NC-SA (Attribution-NonCommercial-ShareAlike)'),
        'cc-by-nc-nd' => t('CC BY-NC-ND (Attribution-NonCommercial-NoDerivatives)'),
        'c' => t('Commercial Copyright (c)'),
        'none' => t('No license specified'),
        'other' => t('Other'),
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

    $fields['data_access'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Data Access Statement'))
      ->setDescription(t('A description of how the data can be accessed.'))
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

    $fields['data_access_notes'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Data Access Notes'))
      ->setDescription(t('Any notes associated with the data license.'))
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

    $fields['public_release'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Public release'))
      ->setDescription(t('Is there a public release date for this data?'))
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

    $fields['public_release_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Public release date'))
      ->setDescription(t('The public release date associated with this data.'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp_optional',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'timestamp',
        'label' => 'inline',
        'settings' => [
          'date_format' => 'html_date',
          'custom_date_format' => '',
          'timezone' => '',
        ],
      ]);

    $file_field_settings = [
      'description_field' => TRUE,
      'file_directory' => 'rothamsted/rothamsted_experiment/[date:custom:Y]-[date:custom:m]',
      'max_filesize' => '',
      'handler' => 'default:file',
      'handler_settings' => [],
    ];
    $fields['file'] = BaseFieldDefinition::create('file')
      ->setLabel(t('File'))
      ->setDescription(t('Upload files associated with this experiment.'))
      ->setRevisionable(TRUE)
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

    $fields['image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Image'))
      ->setDescription(t('Upload files associated with this experiment.'))
      ->setRevisionable(TRUE)
      ->setSettings($file_field_settings)
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

    return $fields;
  }

}
