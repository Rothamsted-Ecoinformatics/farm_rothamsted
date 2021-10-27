<?php

namespace Drupal\farm_rothamsted\Plugin\QuickForm;

/**
 * Operations quick form.
 *
 * @todo This was previously the cultivation quick form so we maintain that ID.
 *
 * @see https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/pull/6#issuecomment-903958799
 *
 * @QuickForm(
 *   id = "farm_rothamsted_cultivation_quick_form",
 *   label = @Translation("Operations"),
 *   description = @Translation("Create operation records."),
 *   helpText = @Translation("Use this form to record operation records."),
 *   permissions = {
 *     "create activity log",
 *   }
 * )
 */
class QuickOperation extends QuickExperimentFormBase {

  /**
   * {@inheritdoc}
   */
  protected $equipmentGroupNames = ['Tractor Equipment', 'Cultivation Equipment'];

}
