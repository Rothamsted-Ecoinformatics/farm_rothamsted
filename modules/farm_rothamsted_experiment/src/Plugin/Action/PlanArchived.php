<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_experiment\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Action that marks a plan as archived.
 */
#[Action(
  id: 'plan_archived_action',
  label: new TranslatableMarkup('Changes plan status to archived'),
  type: 'plan',
)]
class PlanArchived extends PlanStateChangeBase {

  /**
   * {@inheritdoc}
   */
  protected $targetState = 'archived';

}
