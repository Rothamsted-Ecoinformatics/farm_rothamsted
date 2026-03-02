<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_notification\Hook;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Entity hook implementations for farm_rothamsted_notification.
 */
class EntityHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_entity_base_field_info().
   */
  #[Hook('entity_base_field_info')]
  public function entityBaseFieldInfo(EntityTypeInterface $entity_type): array {
    $fields = [];
    if ($entity_type->id() == 'user') {
      $fields['rothamsted_notification_email'] = BaseFieldDefinition::create('boolean')
        ->setLabel($this->t('Email notifications'))
        ->setDescription($this->t('Switch off all e-mail notifications. Please note that there are some e-mail notifications which cannot be switched off for authentic purposes. For example if someone creates a Research Profile on your behalf, or if someone names you on a Research Program, Proposal, Experiment or Design.'))
        ->setDefaultValue(TRUE)
        ->setRevisionable(TRUE)
        ->setRequired(TRUE)
        ->setSettings([
          'on_label' => $this->t('Enabled'),
          'off_label' => $this->t('Disabled'),
        ])
        ->setDisplayConfigurable('form', FALSE)
        ->setDisplayConfigurable('view', TRUE)
        ->setDisplayOptions('view', [
          'type' => 'boolean',
          'label' => 'inline',
        ]);

      $fields['rothamsted_notification_researcher'] = BaseFieldDefinition::create('boolean')
        ->setLabel($this->t('Researcher updates'))
        ->setDescription($this->t('Switch on/off e-mail notifications relating to changes to your Researcher profile in FarmOS. If this is switched off, you will no longer receive notifications if someone other than you edits your Researcher profile (e.g. an administrator). This is on by default. If you leave it on, you will receive e-mails as soon as any changes are made.'))
        ->setDefaultValue(TRUE)
        ->setRevisionable(TRUE)
        ->setSettings([
          'on_label' => $this->t('Enabled'),
          'off_label' => $this->t('Disabled'),
        ])
        ->setDisplayConfigurable('form', FALSE)
        ->setDisplayConfigurable('view', TRUE)
        ->setDisplayOptions('view', [
          'type' => 'boolean',
          'label' => 'inline',
        ]);

      $fields['rothamsted_notification_program'] = BaseFieldDefinition::create('boolean')
        ->setLabel($this->t('Research Program updates'))
        ->setDescription($this->t('Switch on/off e-mail notifications relating to changes to a or any Research Programs you are associated with in FarmOS. If this is switched off, you will no longer receive notifications if someone other than you edits a Research Program where you are named as a PI (e.g. an administrator). This is on by default. If you leave it on, you will receive e-mails as soon as any changes are made.'))
        ->setDefaultValue(TRUE)
        ->setRevisionable(TRUE)
        ->setSettings([
          'on_label' => $this->t('Enabled'),
          'off_label' => $this->t('Disabled'),
        ])
        ->setDisplayConfigurable('form', FALSE)
        ->setDisplayConfigurable('view', TRUE)
        ->setDisplayOptions('view', [
          'type' => 'boolean',
          'label' => 'inline',
        ]);

      $fields['rothamsted_notification_proposal'] = BaseFieldDefinition::create('boolean')
        ->setLabel($this->t('Proposal updates'))
        ->setDescription($this->t('Switch on/off e-mail notifications relating to changes to a or any Proposals you are associated with in FarmOS. If this is switched off, you will no longer receive notifications if someone other than you edits a Proposal you are named on. This is on by default. If you leave it on, you will receive e-mails as soon as any changes are made.'))
        ->setDefaultValue(TRUE)
        ->setRevisionable(TRUE)
        ->setSettings([
          'on_label' => $this->t('Enabled'),
          'off_label' => $this->t('Disabled'),
        ])
        ->setDisplayConfigurable('form', FALSE)
        ->setDisplayConfigurable('view', TRUE)
        ->setDisplayOptions('view', [
          'type' => 'boolean',
          'label' => 'inline',
        ]);

      $fields['rothamsted_notification_experiment'] = BaseFieldDefinition::create('boolean')
        ->setLabel($this->t('Experiment updates'))
        ->setDescription($this->t('Switch on/off e-mail notifications relating to changes to a or any Experiment, Design or Plan you are associated with in FarmOS. If this is switched off, you will no longer receive notifications if someone other than you edits an Experiment, Design or Plan you are associated with. This is on by default. If you leave it on, you will receive e-mails as soon as any changes are made.'))
        ->setDefaultValue(TRUE)
        ->setRevisionable(TRUE)
        ->setSettings([
          'on_label' => $this->t('Enabled'),
          'off_label' => $this->t('Disabled'),
        ])
        ->setDisplayConfigurable('form', FALSE)
        ->setDisplayConfigurable('view', TRUE)
        ->setDisplayOptions('view', [
          'type' => 'boolean',
          'label' => 'inline',
        ]);

      $fields['rothamsted_notification_log'] = BaseFieldDefinition::create('boolean')
        ->setLabel($this->t('Log notifications'))
        ->setDescription($this->t('Switch on/off e-mail notifications for logs. If this is switched of you will no longer receive notifications when someone (e.g. farm staff) adds or edits the logs associated with the experiments you are named on. This is on by default. If you leave it on, you will receive e-mails as soon as new logs are added or any changes are made.'))
        ->setDefaultValue(TRUE)
        ->setRevisionable(TRUE)
        ->setRequired(TRUE)
        ->setSettings([
          'on_label' => $this->t('Enabled'),
          'off_label' => $this->t('Disabled'),
        ])
        ->setDisplayConfigurable('form', FALSE)
        ->setDisplayConfigurable('view', TRUE)
        ->setDisplayOptions('view', [
          'type' => 'boolean',
          'label' => 'inline',
        ]);
    }
    return $fields;
  }

}
