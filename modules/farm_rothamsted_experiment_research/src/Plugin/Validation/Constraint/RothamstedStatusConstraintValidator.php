<?php

namespace Drupal\farm_rothamsted_experiment_research\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the RothamstedStatus constraint.
 */
class RothamstedStatusConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /** @var \Drupal\Core\Field\FieldItemListInterface $value */
    /** @var \Drupal\farm_rothamsted_experiment_research\Plugin\Validation\Constraint\RothamstedStatusConstraint $constraint */

    // Bail if the parent entity does not have a status field.
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $value->getParent()->getValue();
    if (!$entity->hasField('status')) {
      return;
    }

    // Bail if the constraint is not required for the current status.
    $status = $entity->get('status')->first()->get('value')->getValue();
    if (!in_array($status, $constraint->requiredStatuses)) {
      return;
    }

    // Add violation if the field is empty.
    if ($value->isEmpty()) {
      $field_label = $value->getFieldDefinition()->getLabel();
      $entity_type_label = $entity->getEntityType()->getPluralLabel();
      $this->context->buildViolation('@field is required for @status @entity_type.', ['@field' => $field_label, '@status' => $status, '@entity_type' => $entity_type_label])
        ->addViolation();
    }
  }

}
