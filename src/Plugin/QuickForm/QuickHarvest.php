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
  protected bool $productsTab = TRUE;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $id = NULL) {
    $form = parent::buildForm($form, $form_state);

    // Add to the setup tab.
    $setup = &$form['setup'];

    // Type of harvest.
    $harvest_options = [
      $this->t('Combinable crops (incl. sugar beet)'),
      $this->t('Silage pickup'),
      $this->t('Bailing'),
    ];
    $setup['type_of_harvest'] = [
      '#type' => 'select',
      '#title' => $this->t('Type of harvest'),
      '#options' => array_combine($harvest_options, $harvest_options),
      '#required' => TRUE,
    ];

    // Add to the products applied tab.
    $products = &$form['products'];

    // Move recommendation fields to spraying tab.
    foreach (['recommendation_number', 'recommendation_files'] as $field_name) {
      $products[$field_name] = $form['setup'][$field_name];
      unset($form['setup'][$field_name]);
    }

    // Harvest tab.
    $harvest = [
      '#type' => 'details',
      '#title' => $this->t('Harvest'),
      '#group' => 'tabs',
      '#weight' => 0,
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

      // Save a normal batch number.
      $batch_number = $i + 1;

      // Fieldset for each batch.
      $harvest['trailor_batch']['batches'][$i] = [
        '#type' => 'details',
        '#title' => $this->t('Batch @number', ['@number' => $i + 1]),
        '#collapsible' => TRUE,
        '#open' => TRUE,
      ];

      // Add a wrapper for all the quantity fields.
      $wrapper = $this->buildInlineWrapper();

      // Tare.
      $wrapper['tare'] = $this->buildQuantityField([
        'title' => $this->t('Tare'),
        'description' => $this->t('The weight of the trailor, as measured on the scales.'),
        'measure' => ['#value' => 'weight'],
        'units' => ['#value' => 'kg'],
        'label' => ['#value' => "Batch $batch_number tare"],
      ]);

      // Gross weight.
      $wrapper['gross_weight'] = $this->buildQuantityField([
        'title' => $this->t('Gross weight'),
        'description' => $this->t('The weight of the trailor + harvested grain, as measured on the scales.'),
        'measure' => ['#value' => 'weight'],
        'units' => ['#value' => 'kg'],
        'label' => ['#value' => "Batch $batch_number gross weight"],
      ]);

      // Nett weight.
      $wrapper['nett_weight'] = $this->buildQuantityField([
        'title' => $this->t('Nett weight'),
        'description' => $this->t('The weight of the harvested grain.'),
        'measure' => ['#value' => 'weight'],
        'units' => ['#value' => 'kg'],
        'label' => ['#value' => "Batch $batch_number nett weight"],
      ]);

      // Moisture content.
      $wrapper['moisture_content'] = $this->buildQuantityField([
        'title' => $this->t('Moisture content'),
        'description' => $this->t('The moisture content of the grain at the harvest.'),
        'measure' => ['#value' => 'ratio'],
        'units' => ['#value' => '%'],
        'label' => ['#value' => "Batch $batch_number moisture content"],
      ]);

      $harvest['trailor_batch']['batches'][$i]['quantities'] = $wrapper;
    }

    // Number of samples.
    $harvest['number_of_samples'] = $this->buildQuantityField([
      'title' => $this->t('Number of samples'),
      'description' => $this->t('The number of yield samples requested for analysis.'),
      'measure' => ['#value' => 'count'],
      'units' => ['#type' => 'hidden'],
    ]);

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

    // @todo Decide if this should be implemented as a log category.
    $form['operation']['storage_location'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Storage location'),
      '#description' => $this->t('Please select the location where the grain/ straw is being stored. This list can be expanded by adding terms to the ‘Storage Locations’ under the Farm categories taxonomy.'),
      '#required' => TRUE,
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
  protected function getQuantities(array $field_keys, FormStateInterface $form_state): array {

    // Include number of samples.
    $field_keys[] = 'number_of_samples';

    // Include batch quantity keys.
    $batch_count = $form_state->getValue('batch_count');
    for ($i = 0; $i < $batch_count; $i++) {
      $field_keys[] = ['batches', $i, 'quantities', 'tare'];
      $field_keys[] = ['batches', $i, 'quantities', 'gross_weight'];
      $field_keys[] = ['batches', $i, 'quantities', 'nett_weight'];
      $field_keys[] = ['batches', $i, 'quantities', 'moisture_content'];
    }

    return parent::getQuantities($field_keys, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getImageIds(array $field_keys, FormStateInterface $form_state) {
    $field_keys[] = 'digital_harvest_records';
    return parent::getImageIds($field_keys, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareNotes(array $note_fields, FormStateInterface $form_state): array {
    // Prepend additional note fields.
    array_unshift(
      $note_fields,
      ...[
        [
          'key' => 'type_of_harvest',
          'label' => $this->t('Type of harvest'),
        ],
      ]
    );
    return parent::prepareNotes($note_fields, $form_state);
  }

}
