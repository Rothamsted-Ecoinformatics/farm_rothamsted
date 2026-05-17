<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_researcher\Entity;

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
use Drupal\entity\UncacheableEntityPermissionProvider;
use Drupal\farm_comment\FarmCommentHelper;
use Drupal\farm_rothamsted_researcher\Form\ResearcherForm;
use Drupal\farm_rothamsted_researcher\RothamstedResearcherListBuilder;
use Drupal\farm_ui_menu\Menu\DefaultSecondaryLocalTaskProvider;
use Drupal\user\EntityOwnerTrait;
use Drupal\user\UserInterface;

/**
 * Defines the researcher entity class.
 */
#[ContentEntityType(
  id: 'rothamsted_researcher',
  label: new TranslatableMarkup('Researcher'),
  label_collection: new TranslatableMarkup('Researchers'),
  label_singular: new TranslatableMarkup('researcher'),
  label_plural: new TranslatableMarkup('researchers'),
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
    'list_builder' => RothamstedResearcherListBuilder::class,
    'permission_provider' => UncacheableEntityPermissionProvider::class,
    'view_builder' => EntityViewBuilder::class,
    'views_data' => EntityViewsData::class,
    'form' => [
      'add' => ResearcherForm::class,
      'edit' => ResearcherForm::class,
      'delete' => ContentEntityDeleteForm::class,
    ],
    'route_provider' => [
      'default' => AdminHtmlRouteProvider::class,
      'revision' => RevisionRouteProvider::class,
    ],
    'local_task_provider' => [
      'default' => DefaultSecondaryLocalTaskProvider::class,
    ],
  ],
  links: [
    'collection' => '/rothamsted/researcher',
    'canonical' => '/rothamsted/researcher/{rothamsted_researcher}',
    'add-form' => '/rothamsted/researcher/add',
    'edit-form' => '/rothamsted/researcher/{rothamsted_researcher}/edit',
    'delete-form' => '/rothamsted/researcher/{rothamsted_researcher}/delete',
    'version-history' => '/rothamsted/researcher/{rothamsted_researcher}/revisions',
    'revision' => '/rothamsted/researcher/{rothamsted_researcher}/revisions/{rothamsted_researcher_revision}/view',
    'revision-revert-form' => '/rothamsted/researcher/{rothamsted_researcher}/revisions/{rothamsted_researcher_revision}/revert',
  ],
  admin_permission: 'administer rothamsted resarchers',
  base_table: 'rothamsted_researcher',
  data_table: 'rothamsted_researcher_data',
  revision_table: 'rothamsted_researcher_revision',
  translatable: TRUE,
  show_revision_ui: TRUE,
  revision_metadata_keys: [
    'revision_user' => 'revision_user',
    'revision_created' => 'revision_created',
    'revision_log_message' => 'revision_log_message',
  ],
)]
class RothamstedResearcher extends RevisionableContentEntityBase implements RothamstedResearcherInterface {

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
      ->setDescription(t('The name of the person.'))
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
      ->setDescription(t('The user ID of author of the researcher.'))
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
      ->setDescription(t('The time that the researcher was created.'))
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
      ->setDescription(t('The time that the researcher was last edited.'))
      ->setRevisionable(TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setDescription(t('The status of the researcher.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'active' => t('Active'),
        'archived' => t('Archived'),
      ])
      ->setDefaultValue('active')
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
        'weight' => 10,
      ]);

    $fields['farm_user'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('farmOS user profile'))
      ->setDescription(t('The user profile if they have access to farmOS.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Honorific prefix'))
      ->setDescription(t('The title or honorific prefix of the person.'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'settings' => [
          'size' => 10,
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'label' => 'inline',
        'weight' => 10,
      ]);

    $fields['job_title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Job title'))
      ->setDescription(t('The job title of the researcher.'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'label' => 'inline',
        'weight' => 10,
      ]);

    $fields['role'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Research role'))
      ->setDescription(t('The role the person plays in relation to experiments.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('allowed_values', [
        'data_curator' => t('Data Curator'),
        'lead_scientist' => t('Lead Scientist'),
        'post_doctoral_research_scientist' => t('Post-Doctoral Research Scientist'),
        'phd_student' => t('PhD Student'),
        'research_technician' => t('Research Technician'),
        'statistician' => t('Statistician'),
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
        'weight' => 10,
      ]);

    $fields['organization'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Organization'))
      ->setDescription(t('The name of the organization the person works for.'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'settings' => [
          'size' => 60,
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'label' => 'inline',
        'weight' => 10,
      ]);

    $fields['department'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Department'))
      ->setDescription(t('The name of the department the person belongs to.'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'settings' => [
          'size' => 60,
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'label' => 'inline',
        'weight' => 10,
      ]);

    $fields['orcid'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Orcid ID'))
      ->setDescription(t("The person's OrcidID. See https://orcid.org/ for further details and to register."))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'rothamsted_orcid_link',
        'settings' => [
          'size' => 25,
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'rothamsted_orcid_link',
        'label' => 'inline',
        'weight' => 10,
      ]);

    $fields['notes'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Notes'))
      ->setDescription(t('Additional notes about the person and their responsibilities.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'inline',
        'weight' => 10,
      ]);

    // Add comment field.
    $fields['comment'] = FarmCommentHelper::commentBaseFieldDefinition('rothamsted_researcher');
    $fields['comment']->setDisplayOptions('form', [
      'type' => 'comment_default',
      'region' => 'hidden',
    ]);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getNotificationEmail(bool $force = FALSE, ?string $notification_type = NULL): ?string {

    // Bail if no farm_user.
    if ($this->get('farm_user')->isEmpty()) {
      return NULL;
    }

    // Get user.
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->get('farm_user')->entity;

    // Bail if not forced and emails are disabled.
    $emails_enabled = $user->get('rothamsted_notification_email')->value;
    if (!$force && !$emails_enabled) {
      return NULL;
    }

    // Add logic for notification types.
    switch ($notification_type) {

      case 'researcher':
      case 'program':
      case 'proposal':
      case 'experiment':
      case 'log':
        if (!$force && !$user->get("rothamsted_notification_$notification_type")?->value) {
          return NULL;
        }
        break;
    }

    // Return the user email if not blocked or prevented above.
    return $user->isBlocked() ? NULL : $user->getEmail();
  }

}
