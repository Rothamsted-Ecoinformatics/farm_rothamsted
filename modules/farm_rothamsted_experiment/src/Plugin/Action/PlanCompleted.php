<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_experiment\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\plan\Plugin\Action\PlanStateChangeBase;

/**
 * Action that marks a plan as completed.
 */
#[Action(
  id: 'plan_completed_action',
  label: new TranslatableMarkup('Changes plan status to completed'),
  type: 'plan',
)]
class PlanCompleted extends PlanStateChangeBase {

  /**
   * {@inheritdoc}
   */
  protected $targetState = 'completed';

}
