<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_quick\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\farm_quick\Plugin\Action\QuickFormActionBase;
use Drupal\farm_rothamsted_quick\Plugin\Action\Derivative\QuickExperimentActionDeriver;

/**
 * Action for completing experiment quick forms.
 */
#[Action(
  id: 'farm_rothamsted_quick_experiment',
  action_label: new TranslatableMarkup('Record experiment quick form'),
  deriver: QuickExperimentActionDeriver::class,
)]
class QuickExperimentAction extends QuickFormActionBase {

  /**
   * {@inheritdoc}
   */
  public function getQuickFormId(): string {
    // Because this uses a deriver, the quick form id is the second part.
    $parts = explode(':', $this->getPluginId());
    return $parts[1];
  }

  /**
   * Implement the old method name with misspelling.
   *
   * This can be removed once https://github.com/farmOS/farmOS/pull/703 is
   * merged and deployed.
   */
  public function getQuckFormId(): string {
    return $this->getQuickFormId();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {

    // Only allow plant and plot assets.
    $allowed_bundle = AccessResult::forbiddenIf(!in_array($object->bundle(), ['plant', 'plot']));

    // Ensure view access on the asset.
    $view_access = $object->access('view', $account, TRUE);

    // Return the result.
    $access = $view_access->orIf($allowed_bundle);
    return $return_as_object ? $access : $access->isAllowed();
  }

}
