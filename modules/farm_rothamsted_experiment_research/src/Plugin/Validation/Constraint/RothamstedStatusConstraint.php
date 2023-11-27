<?php

namespace Drupal\farm_rothamsted_experiment_research\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks field is required dependent on status.
 *
 * @Constraint(
 *   id = "RothamstedStatus",
 *   label = @Translation("Rothamsted Status", context = "Validation"),
 * )
 */
class RothamstedStatusConstraint extends Constraint {

  /**
   * Array of status that the constraint is required on.
   *
   * @var array
   */
  public array $requiredStatuses = [];

  /**
   * {@inheritDoc}
   */
  public function getRequiredOptions() {
    return ['requiredStatuses'];
  }

  /**
   * {@inheritDoc}
   */
  public function __set($option, $value) {
    if ('requiredStatuses' === $option) {
      $this->requiredStatuses = $value;
      return;
    }

    parent::__set($option, $value);
  }

  /**
   * {@inheritDoc}
   */
  public function __get($option): mixed {
    if ('requiredStatuses' === $option) {
      return $this->requiredStatuses;
    }

    return parent::__get($option);
  }

  /**
   * {@inheritDoc}
   */
  public function __isset($option) {
    if ('requiredStatuses' === $option) {
      return TRUE;
    }

    return parent::__isset($option);
  }

}
