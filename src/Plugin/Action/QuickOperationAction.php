<?php

namespace Drupal\farm_rothamsted\Plugin\Action;

use Drupal\farm_quick\Plugin\Action\QuickFormActionBase;

/**
 * Action for recording egg harvests.
 *
 * @Action(
 *   id = "farm_rothamsted_cultivation",
 *   label = @Translation("Record operation"),
 *   type = "asset",
 *   confirm_form_route_name =
 *    "farm.quick.farm_rothamsted_cultivation_quick_form"
 * )
 */
class QuickOperationAction extends QuickFormActionBase {

  /**
   * {@inheritdoc}
   */
  public function getQuckFormId(): string {
    return 'farm_rothamsted_cultivation_quick_form';
  }

}
