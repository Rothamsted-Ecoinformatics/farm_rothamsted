<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_experiment\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\plan\Plugin\Action\PlanStateChangeBase;

/**
 * Action that marks a plan as cancelled.
 */
#[Action(
  id: 'plan_cancelled_action',
  label: new TranslatableMarkup('Changes plan status to cancelled'),
  type: 'plan',
)]
class PlanCancelled extends PlanStateChangeBase {

  /**
   * {@inheritdoc}
   */
  protected $targetState = 'cancelled';

}
