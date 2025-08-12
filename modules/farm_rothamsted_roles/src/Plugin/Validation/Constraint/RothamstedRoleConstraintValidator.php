<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_roles\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the RestrictedViewerRole constraint.
 */
class RothamstedRoleConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  use AutowireTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entity_type_manager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $value */

    // Get the user and user's role IDs.
    $user = $value->getParent()->getValue();
    $role_ids = array_column($value->getValue(), 'target_id');

    // Ignore special roles role.
    $role_ids = array_diff($role_ids, ['authenticated', 'farm_account_admin']);

    // Add violation if the user has more than two roles.
    if (count($role_ids) > 2) {
      $this->context->buildViolation(
        "%user cannot be assigned more than two roles.",
        ['%user' => $user->label()]
      )
        ->addViolation();
    }

    if (count($role_ids) === 2) {

      // The restricted viewer role cannot be combined with any other role.
      if (in_array('rothamsted_research_restricted_viewer', $role_ids)) {
        $this->context->buildViolation(
          "%user cannot be assigned the Restricted Viewer role combined with any other role.",
          ['%user' => $user->label()]
        )
          ->addViolation();
      }

      // The research reviewer can be combined with any other role, allow this.
      if (in_array('rothamsted_research_reviewer', $role_ids)) {
        return;
      }

      // Operator roles can be combined with any of the researcher roles.
      $operator_roles = [
        'rothamsted_operator_basic',
        'rothamsted_operator_advanced'
      ];
      $research_roles = [
        'rothamsted_research_lead',
        'rothamsted_research_editor'
      ];
      $has_operator = count(array_intersect($operator_roles, $role_ids)) > 0;
      $has_research = count(array_intersect($research_roles, $role_ids)) > 0;

      // If the user has 2 roles, but does not have an operator and a research
      // role, add a violation.
      if (!($has_operator && $has_research)) {
        // The restricted viewer role cannot be combined with any other role.
        $this->context->buildViolation(
          "%user can only have Operator and Research roles combined.",
          ['%user' => $user->label()]
        )
          ->addViolation();
      }
    }
  }
}
