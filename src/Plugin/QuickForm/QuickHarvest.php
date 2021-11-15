<?php

namespace Drupal\farm_rothamsted\Plugin\QuickForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\farm_quick\QuickFormBase;
use Drupal\farm_quick\Traits\QuickLogTrait;

/**
 * Harvest quick form.
 *
 * @QuickForm(
 *   id = "farm_rothamsted_harvest_quick_form",
 *   label = @Translation("Harvest"),
 *   description = @Translation("Create harvest records."),
 *   helpText = @Translation("Use this form to record harvest records."),
 *   permissions = {
 *     "create harvest log",
 *   }
 * )
 */
class QuickHarvest extends QuickExperimentFormBase {

  use QuickLogTrait;

  /**
   * {@inheritdoc}
   */
  protected $equipmentGroupNames = ['Tractor Equipment', 'Harvest Machinery Equipment'];

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $id = NULL) {

    // Harvest quantity.
    $form['quantity'] = [
      '#type' => 'number',
      '#title' => $this->t('Quantitly'),
      '#min' => 0,
      '#step' => 1,
      '#required' => TRUE,
    ];

    // Harvest units.
    $harvest_units = [
      '',
      'g',
      'kg',
      'Gt'
    ];

    // @todo Each harvest - units from hard coded list.
    $form['units'] = [
      '#type' => 'select',
      '#title' => $this->t('Units'),
      '#options' => array_combine($harvest_units, $harvest_units),
    ];

    // @todo Each quantity - measure, value, units, label.
    // @todo AJAX for each quantity.

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Draft an harvest log from the user-submitted data.
    $quantity = $form_state->getValue('quantity');
    $units = $form_state->getValue('units');

    $log = [
      'name' => $this->t('Harvested @quantity @units', ['@quantity' => $quantity, '@units' => $units]),
      'type' => 'harvest',
      'quantity' => [
        [
          'measure' => 'unit',
          'value' => $quantity,
          'unit' => $units,
        ],
      ],
    ];

    // Create the log.
    $this->createLog($log);
  }

}
