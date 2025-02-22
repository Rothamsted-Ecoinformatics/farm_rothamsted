<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_experiment\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\plan\Plugin\Action\PlanStateChangeBase;

/**
 * Action that marks a plan as requested.
 */
#[Action(
  id: 'plan_requested_action',
  label: new TranslatableMarkup('Changes plan status to requested'),
  type: 'plan',
)]
class PlanRequested extends PlanStateChangeBase {

  /**
   * {@inheritdoc}
   */
  protected $targetState = 'requested';

}
