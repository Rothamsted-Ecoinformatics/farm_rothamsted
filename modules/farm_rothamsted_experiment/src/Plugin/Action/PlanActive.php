<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_experiment\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Action that marks a plan as active.
 */
#[Action(
  id: 'plan_active_action',
  label: new TranslatableMarkup('Changes plan status to active'),
  type: 'plan',
)]
class PlanActive extends PlanStateChangeBase {

  /**
   * {@inheritdoc}
   */
  protected $targetState = 'active';

}
