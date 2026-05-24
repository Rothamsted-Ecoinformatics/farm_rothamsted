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
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\entity\EntityViewsData;
use Drupal\entity\Routing\AdminHtmlRouteProvider;
use Drupal\entity\Routing\RevisionRouteProvider;
use Drupal\entity\UncacheableEntityAccessControlHandler;
use Drupal\farm_comment\FarmCommentHelper;
use Drupal\farm_rothamsted_experiment_research\Form\EntityStatusChangeActionForm;
use Drupal\farm_rothamsted_experiment_research\Form\ExperimentEntityForm;
use Drupal\farm_rothamsted_experiment_research\ResearchEntityPermissionProvider;
use Drupal\farm_rothamsted_experiment_research\RothamstedEntityListBuilder;
use Drupal\farm_rothamsted_experiment_research\Routing\EntityStatusChangeRouteProvider;
use Drupal\farm_ui_menu\Menu\DefaultSecondaryLocalTaskProvider;
use Drupal\link\LinkItemInterface;
use Drupal\user\EntityOwnerTrait;
use Drupal\user\UserInterface;

/**
 * Defines the research experiment entity class.
 */
#[ContentEntityType(
  id: 'rothamsted_experiment',
  label: new TranslatableMarkup('Experiment'),
  label_collection: new TranslatableMarkup('Experiments'),
  label_singular: new TranslatableMarkup('experiment'),
  label_plural: new TranslatableMarkup('experiments'),
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
      'add' => ExperimentEntityForm::class,
      'edit' => ExperimentEntityForm::class,
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
    'canonical' => '/rothamsted/experiment/{rothamsted_experiment}',
    'collection' => '/rothamsted/experiment',
    'add-form' => '/rothamsted/experiment/add',
    'edit-form' => '/rothamsted/experiment/{rothamsted_experiment}/edit',
    'delete-form' => '/rothamsted/experiment/{rothamsted_experiment}/delete',
    'version-history' => '/rothamsted/experiment/{rothamsted_experiment}/revisions',
    'revision' => '/rothamsted/experiment/{rothamsted_experiment}/revisions/{rothamsted_experiment_revision}/view',
    'revision-revert-form' => '/rothamsted/experiment/{rothamsted_experiment}/revisions/{rothamsted_experiment_revision}/revert',
    'entity-status-action-form' => '/rothamsted/experiment/change-status',
  ],
  admin_permission: 'administer resarch experiments',
  base_table: 'rothamsted_experiment',
  data_table: 'rothamsted_experiment_data',
  revision_table: 'rothamsted_experiment_revision',
  translatable: TRUE,
  show_revision_ui: TRUE,
  revision_metadata_keys: [
    'revision_user' => 'revision_user',
    'revision_created' => 'revision_created',
    'revision_log_message' => 'revision_log_message',
  ],
)]
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
      ->setLabel(new TranslatableMarkup('Name'))
      ->setDescription(new TranslatableMarkup('The name of the experiment.'))
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
      ->setDescription(new TranslatableMarkup('The user ID of author of the research experiment.'))
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
      ->setDescription(new TranslatableMarkup('The time that the research experiment was created.'))
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
      ->setDescription(new TranslatableMarkup('The time that the research experiment was last edited.'))
      ->setRevisionable(TRUE);

    $fields['program'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Related Research Programs'))
      ->setDescription(new TranslatableMarkup('The research program which this experiment is part of.'))
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

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Status'))
      ->setDescription(new TranslatableMarkup('The status of the experiment.'))
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
      ->setDescription(new TranslatableMarkup('Any notes about the experiment status.'))
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

    $fields['code'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Experiment code'))
      ->setDescription(new TranslatableMarkup('The experiment code.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setRevisionable(TRUE)
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
      ->setLabel(new TranslatableMarkup('Abbreviation'))
      ->setDescription(new TranslatableMarkup('The abbreviated name of the experiment.'))
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
      ->setLabel(new TranslatableMarkup('Description'))
      ->setDescription(new TranslatableMarkup('A description of the experiment.'))
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
      ->setLabel(new TranslatableMarkup('Category'))
      ->setDescription(new TranslatableMarkup('The experiment category.'))
      ->setRevisionable(TRUE)
      ->setSetting('allowed_values_function', 'farm_rothamsted_experiment_research_experiment_category_field_allowed_values')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'list_default',
        'label' => 'inline',
      ]);

    $fields['start'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Start year'))
      ->setDescription(new TranslatableMarkup('The start year of the experiment.'))
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
      ->setDescription(new TranslatableMarkup('The end year of the experiment.'))
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

    $fields['researcher'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Researchers'))
      ->setDescription(new TranslatableMarkup('Researchers that are associated with this experiment.'))
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

    $fields['website'] = BaseFieldDefinition::create('link')
      ->setLabel(new TranslatableMarkup('Website'))
      ->setDescription(new TranslatableMarkup('The URL for the experiment website.'))
      ->setRevisionable(TRUE)
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

    $fields['confidential_treatment'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Confidential treatments'))
      ->setDescription(new TranslatableMarkup('Are the treatments being applied in this experiment confidential?'))
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

    $fields['data_license'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Data license'))
      ->setDescription(new TranslatableMarkup('The license associated with the experiment data.'))
      ->setRevisionable(TRUE)
      ->setSetting('allowed_values', [
        'public_domain' => new TranslatableMarkup('Public Domain'),
        'cc0' => new TranslatableMarkup('CC0 (No Rights Reserved, Public Domain'),
        'pddl' => new TranslatableMarkup('PDDL (Open Data Commons Public Domain Dedication and License)'),
        'cc-by' => new TranslatableMarkup('CC-BY (Attribution)'),
        'cdla-permissive' => new TranslatableMarkup('CDLA-Permissive (Community Data License Agreement – Permissive)'),
        'odc-by' => new TranslatableMarkup('ODC-BY (Open Data Commons Attribution License)'),
        'cc-by-sa' => new TranslatableMarkup('CC BY-SA (Attribution-ShareAlike)'),
        'cdla-sharing' => new TranslatableMarkup('CDLA-Sharing (Community Data License Agreement)'),
        'odc-odbl' => new TranslatableMarkup('ODC-ODbL (Open Data Commons Open Database License)'),
        'cc-by-nc' => new TranslatableMarkup('CC BY-NC (Attribution-NonCommercial)'),
        'cc-by-nd' => new TranslatableMarkup('CC BY-ND (Attribution-NoDerivatives)'),
        'cc-by-nc-sa' => new TranslatableMarkup('CC BY-NC-SA (Attribution-NonCommercial-ShareAlike)'),
        'cc-by-nc-nd' => new TranslatableMarkup('CC BY-NC-ND (Attribution-NonCommercial-NoDerivatives)'),
        'c' => new TranslatableMarkup('Commercial Copyright (c)'),
        'none' => new TranslatableMarkup('No license specified'),
        'other' => new TranslatableMarkup('Other'),
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
      ->setLabel(new TranslatableMarkup('Data Access Statement'))
      ->setDescription(new TranslatableMarkup('A description of how the data can be accessed.'))
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
      ->setLabel(new TranslatableMarkup('Data Access Notes'))
      ->setDescription(new TranslatableMarkup('Any notes associated with the data license.'))
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
      ->setLabel(new TranslatableMarkup('Public release'))
      ->setDescription(new TranslatableMarkup('Is there a public release date for this data?'))
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

    $fields['public_release_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Public release date'))
      ->setDescription(new TranslatableMarkup('The public release date associated with this data.'))
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

    // Common file field settings.
    $file_settings = [
      'file_directory' => 'rothamsted/rothamsted_experiment/[date:custom:Y]-[date:custom:m]',
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
      ->setDescription(new TranslatableMarkup('Upload files associated with this experiment.'))
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
      ->setDescription(new TranslatableMarkup('Upload files associated with this experiment.'))
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
      ->setDescription(new TranslatableMarkup('Links to external website and documents associated with the experiment.'))
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
    $fields['comment'] = FarmCommentHelper::commentBaseFieldDefinition('rothamsted_experiment');

    return $fields;
  }

}
