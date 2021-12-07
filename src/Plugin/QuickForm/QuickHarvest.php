<?php

namespace Drupal\farm_rothamsted\Plugin\QuickForm;

use Drupal\Core\Form\FormStateInterface;
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
    $form = parent::buildForm($form, $form_state);

    // Require the operator field.
    $form['users']['#required'] = TRUE;

    // Allow date and time to be specified.
    $form['date']['#date_part_order'] = ['year', 'month', 'day', 'hour', 'minute'];

    // Harvest quantity.
    $form['quantity']['count'] = [
      '#type' => 'select',
      '#title' => $this->t('How many quantities are associated with this harvest?'),
      '#options' => array_combine(range(1, 5), range(1, 5)),
      '#default_value' => 1,
      '#ajax' => [
        'callback' => [$this, 'farm_rothamsted_harvest_quick_form_quantities_ajax'],
        'event' => 'change',
        'wrapper' => 'farm-rothamsted-harvest-quantities',
      ],
    ];

    // Create a wrapper around all quantity fields, for AJAX replacement.
    $form['quantity']['quantities'] = [
      '#prefix' => '<div id="farm-rothamsted-harvest-quantities">',
      '#suffix' => '</div>',
    ];

    // Add fields for each quantity.
    $form['quantity']['quantities']['#TREE'] = TRUE;
    $quantities = $form_state->getValue('count', 1);
    for ($i = 0; $i < $quantities; $i++) {

      // Fieldset for each quantity.
      $form['quantity']['quantities'][$i] = [
        '#type' => 'details',
        '#title' => $this->t('Quantity @number', ['@number' => $i + 1]),
        '#collapsible' => TRUE,
        '#open' => TRUE,
      ];

      // Quantity measure (weight or volume).
      $form['quantity']['quantities'][$i]['measure'] = [
        '#type' => 'select',
        '#title' => $this->t('Quantity measure'),
        '#options' => [
          'weight' => $this->t('Weight'),
          'volume' => $this->t('Volume'),
        ],
      ];

      // Quantity value.
      $form['quantity']['quantities'][$i]['value'] = [
        '#type' => 'number',
        '#title' => $this->t('Quantity value'),
      ];

      // Quantity units.
      $form['quantity']['quantities'][$i]['units'] = [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'taxonomy_term',
        '#title' => $this->t('Quantity units'),
        '#selection_settings' => [
          'target_bundles' => ['unit'],
        ],
      ];
    }

    return $form;
  }

  /**
   * Form ajax function for harvest quick form quantities.
   */
  public function farm_rothamsted_harvest_quick_form_quantities_ajax(array $form, FormStateInterface $form_state) {
    return $form['quantity']['quantities'];
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
