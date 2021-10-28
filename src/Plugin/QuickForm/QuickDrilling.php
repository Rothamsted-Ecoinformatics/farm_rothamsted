<?php

namespace Drupal\farm_rothamsted\Plugin\QuickForm;

use Drupal\Core\Form\FormStateInterface;

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

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // @todo Select parent plant_type term.
    // @todo Select child plant_type term.

    // RES lot number.
    $form['lot_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('RES lot number'),
    ];

    // Thousand grain weight.
    $form['tgw'] = [
      '#type' => 'number',
      '#title' => $this->t('Thousand grain weight (TGW)'),
      '#input_group' => TRUE,
      '#field_suffix' => $this->t('grams'),
    ];

    // Seed rate (SM2).
    $form['rate_sm2'] = [
      '#type' => 'number',
      '#title' => $this->t('Seed rate (SM<sup>2</sup>)'),
      '#input_group' => TRUE,
      '#field_suffix' => $this->t('SM<sup>2</sup>'),
    ];

    // Seed rate (Kg/Ha).
    $form['rate_kgha'] = [
      '#type' => 'number',
      '#title' => $this->t('Seed rate (Kg/Ha)'),
      '#input_group' => TRUE,
      '#field_suffix' => $this->t('Kg/Ha'),
    ];

    return $form;
  }

}
