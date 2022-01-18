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
  protected $logType = 'harvest';

  /**
   * {@inheritdoc}
   */
  protected $tractorField = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $machineryGroupNames = ['Harvest Machinery Equipment'];

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $id = NULL) {
    $form = parent::buildForm($form, $form_state);

    // Harvest tab.
    $harvest = [
      '#type' => 'details',
      '#title' => $this->t('Harvest'),
      '#group' => 'tabs',
      '#weight' => 10,
    ];

    // Trailor batch count.
    $harvest['trailor_batch']['batch_count'] = [
      '#type' => 'select',
      '#title' => $this->t('How many trailor batches are associated with this harvest?'),
      '#options' => array_combine(range(1, 5), range(1, 5)),
      '#default_value' => 1,
      '#ajax' => [
        'callback' => [$this, 'trailorBatchesCallback'],
        'event' => 'change',
        'wrapper' => 'farm-rothamsted-harvest-trailor-batches',
      ],
    ];

    // Create a wrapper around all trailor batch fields, for AJAX replacement.
    $harvest['trailor_batch']['batches'] = [
      '#prefix' => '<div id="farm-rothamsted-harvest-trailor-batches">',
      '#suffix' => '</div>',
    ];

    // Add fields for each trailor batch.
    $harvest['trailor_batch']['batches']['#tree'] = TRUE;
    $quantities = $form_state->getValue('batch_count', 1);
    for ($i = 0; $i < $quantities; $i++) {

      // Fieldset for each batch.
      $harvest['trailor_batch']['batches'][$i] = [
        '#type' => 'details',
        '#title' => $this->t('Batch @number', ['@number' => $i + 1]),
        '#collapsible' => TRUE,
        '#open' => TRUE,
      ];

      // Tare.
      $harvest['trailor_batch']['batches'][$i]['tare'] = [
        '#type' => 'number',
        '#title' => $this->t('Tare'),
        '#description' => $this->t('The weight of the trailor, as measured on the scales.'),
        '#field_suffix' => $this->t('kg. t'),
        '#required' => FALSE,
      ];

      // Gross weight.
      $harvest['trailor_batch']['batches'][$i]['gross_weight'] = [
        '#type' => 'number',
        '#title' => $this->t('Gross weight'),
        '#description' => $this->t('The weight of the trailor + harvested grain, as measured on the scales.'),
        '#field_suffix' => $this->t('kg. t'),
        '#required' => FALSE,
      ];

      // Nett weight.
      $harvest['trailor_batch']['batches'][$i]['nett_weight'] = [
        '#type' => 'number',
        '#title' => $this->t('Nett weight'),
        '#description' => $this->t('The weight of the harvested grain.'),
        '#field_suffix' => $this->t('kg. t'),
        '#required' => FALSE,
      ];

      // Moisture content.
      $harvest['trailor_batch']['batches'][$i]['moisture_content'] = [
        '#type' => 'number',
        '#title' => $this->t('Moisture content'),
        '#description' => $this->t('The moisture content of the grain at the harvest.'),
        '#field_suffix' => $this->t('%'),
        '#required' => FALSE,
      ];
    }

    // Number of samples.
    $harvest['number_of_samples'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of samples'),
      '#description' => $this->t('The number of yield samples requested for analysis.'),
      '#required' => FALSE,
    ];

    // Harvest lot number.
    $harvest['harvest_lot_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Harvest lot number'),
      '#description' => $this->t('The RRES harvest number, where applicable.'),
      '#required' => FALSE,
    ];

    // Condition of the grain at storage.
    $harvest['grain_storage_condition'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Condition of the grain at storage'),
      '#description' => $this->t('The RRES harvest number, where applicable.'),
      '#required' => FALSE,
    ];

    // Add the harvest tab and fields to the form.
    $form['harvest'] = $harvest;

    // Digital harvest records.
    // @todo Determine the final file upload location.
    $form['operation']['digital_harvest_records'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Digital harvest record(s)'),
      '#description' => $this->t('An upload of any digital harvest records, where applicable.'),
      '#upload_location' => 'private://quick',
      '#upload_validators' => [
        'file_validate_extensions' => ['pdf png gif jpg jpeg'],
      ],
      '#required' => FALSE,
    ];

    return $form;
  }

  /**
   * Form ajax function for harvest quick form batches.
   */
  public function trailorBatchesCallback(array $form, FormStateInterface $form_state) {
    return $form['harvest']['trailor_batch']['batches'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
  }

}
