<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_experiment_research\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity\Form\EntityDuplicateFormInterface;
use Drupal\entity\Form\EntityDuplicateFormTrait;

/**
 * Form for duplicating proposals. Depends on entity API module.
 */
class DuplicateProposalForm extends ProposalEntityForm implements EntityDuplicateFormInterface {

  use EntityDuplicateFormTrait;

  /**
   * {@inheritdoc}
   */
  public function setEntity(EntityInterface $entity) {
    parent::setEntity($entity);

    // Define fields to clear on duplicate.
    $fields_to_clear = [
      'name',
      'design',
      'plan',
      'status_notes',
      'reviewer',
      'study_id',
    ];
    foreach ($fields_to_clear as $field_name) {
      $this->entity->set($field_name, NULL);
    }
    $this->entity->set('status', 'draft');
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSourceEntity(EntityInterface $entity) {
    $this->sourceEntity = $entity;
    $source_name = $this->sourceEntity->label();
    $source_link = $this->sourceEntity->toUrl()->setAbsolute()->toString();
    $status_notes = "This proposal was created by duplicating \"$source_name\". Please refer to that proposal for previous versions and a revision history. $source_link";
    $this->entity->set('status_notes', $status_notes);
    $this->entity->setRevisionLogMessage($status_notes);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Only display message on initial form load.
    if (empty($form_state->getUserInput())) {
      $source_name = $this->sourceEntity->label();
      $this->messenger()->addWarning("This will create a new proposal from \"$source_name\"");
    }
    return parent::buildForm($form, $form_state);
  }

}
