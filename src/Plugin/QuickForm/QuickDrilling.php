<?php

namespace Drupal\farm_rothamsted\Plugin\QuickForm;

/**
 * Drilling quick form.
 *
 * @QuickForm(
 *   id = "farm_rothamsted_drilling_quick_form",
 *   label = @Translation("Drilling"),
 *   description = @Translation("Create drilling records."),
 *   helpText = @Translation("Use this form to record drilling records."),
 *   permissions = {
 *     "create seeding log",
 *   }
 * )
 */
class QuickDrilling extends QuickExperimentFormBase {

  /**
   * {@inheritdoc}
   */
  protected $equipmentGroupNames = ['Tractor Equipment', 'Drilling Equipment'];

}
