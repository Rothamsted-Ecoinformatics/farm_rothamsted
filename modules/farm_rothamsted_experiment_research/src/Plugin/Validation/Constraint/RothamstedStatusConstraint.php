<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_experiment_research\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Checks field is required dependent on status.
 */
#[Constraint(
  id: 'RothamstedStatus',
  label: new TranslatableMarkup('Rothamsted Status', [], ['context' => 'Validation']),
)]
class RothamstedStatusConstraint extends SymfonyConstraint {

  /**
   * Array of status that the constraint is required on.
   *
   * @var array
   */
  public array $requiredStatuses = [];

  /**
   * {@inheritDoc}
   */
  public function getRequiredOptions(): array {
    return ['requiredStatuses'];
  }

  /**
   * {@inheritDoc}
   */
  public function __set($option, $value): void {
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
  public function __isset($option): bool {
    if ('requiredStatuses' === $option) {
      return TRUE;
    }

    return parent::__isset($option);
  }

}
