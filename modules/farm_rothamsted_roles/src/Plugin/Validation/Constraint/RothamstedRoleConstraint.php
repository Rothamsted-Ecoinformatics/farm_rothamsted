<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_roles\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Enforces constraints for Rothamsted roles.
 */
#[Constraint(
  id: 'rothamsted_role_constraint',
  label: new TranslatableMarkup('Rothamsted Role Constraint', ['context' => 'Validation']),
)]
class RothamstedRoleConstraint extends SymfonyConstraint {

}
