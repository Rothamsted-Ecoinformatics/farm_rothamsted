<?php

namespace Drupal\farm_rothamsted\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\farm_quick\Plugin\Action\QuickFormActionBase;

/**
 * Action for completing experiment quick forms.
 *
 * @Action(
 *   id = "farm_rothamsted_quick_experiment",
 *   action_label = @Translation("Record experiment quick form"),
 *   deriver = "Drupal\farm_rothamsted\Plugin\Action\Derivative\QuickExperimentActionDeriver",
 * )
 */
class QuickExperimentAction extends QuickFormActionBase {

  /**
   * {@inheritdoc}
   */
  public function getQuckFormId(): string {
    // Because this uses a deriver, the quick form id is the second part.
    $parts = explode(':', $this->getPluginId());
    return $parts[1];
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {

    // Only allow plant and plot assets.
    $allowed_bundle = AccessResult::forbiddenIf(!in_array($object->bundle(), ['plant', 'plot']));

    // Ensure view access on the asset.
    $view_access = $object->access('view', $account, TRUE);

    // Return the result.
    $access = $view_access->orIf($allowed_bundle);
    return $return_as_object ? $access : $access->isAllowed();
  }

}
