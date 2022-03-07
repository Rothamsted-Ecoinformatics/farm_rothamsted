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

    // Add weight to equipment settings.
    $setup['equipment_settings']['#weight'] = 10;

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

    // Trailer Load tab.
    $trailer = [
      '#type' => 'details',
      '#title' => $this->t('Trailer Load'),
      '#group' => 'tabs',
      '#weight' => 0,
    ];

    // Number of bales.
    $trailer['number_of_bales'] = $this->buildQuantityField([
      'title' => $this->t('Number of bales on the trailer'),
      'description' => $this->t('Please give the total number of bales on the trailer, where relevant.'),
      'measure' => ['#value' => 'count'],
      'units' => ['#type' => 'hidden'],
    ]);

    // Common trailer weight units.
    $trailer_weight_units = [
      't' => 'tonnes',
      'kg' => 'kilogrammes',
    ];

    $trailer['weight_wrapper'] = $this->buildInlineWrapper();

    // Tare.
    $trailer['weight_wrapper']['tare'] = $this->buildQuantityField([
      'title' => $this->t('Trailer tare'),
      'description' => $this->t('The weight of the trailer, as measured on the scales.'),
      'measure' => ['#value' => 'weight'],
      'units' => ['#options' => $trailer_weight_units],
    ]);

    // Gross weight.
    $trailer['weight_wrapper']['gross_weight'] = $this->buildQuantityField([
      'title' => $this->t('Gross weight'),
      'description' => $this->t('The weight of the trailer + harvested grain, as measured on the scales.'),
      'measure' => ['#value' => 'weight'],
      'units' => ['#options' => $trailer_weight_units],
    ]);

    // Nett weight.
    // @todo Make this required if type of harvest is combinable crops.
    $trailer['weight_wrapper']['nett_weight'] = $this->buildQuantityField([
      'title' => $this->t('Nett weight'),
      'description' => $this->t('The weight of the harvested grain.'),
      'measure' => ['#value' => 'weight'],
      'units' => ['#options' => $trailer_weight_units],
    ]);

    // Moisture content.
    // @todo Make this required if type of harvest is combinable crops.
    $trailer['moisture_content'] = $this->buildQuantityField([
      'title' => $this->t('Moisture content'),
      'description' => $this->t('The moisture content of the grain at the harvest.'),
      'measure' => ['#value' => 'ratio'],
      'units' => ['#value' => '%'],
    ]);

    // Grain sample number.
    $trailer['grain_sample_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Grain sample number'),
      '#description' => $this->t('If a grain sample is taken from this trailer for testing and analysis, please record the sample number here.'),
    ];

    // Condition of the grain at storage.
    $trailer['storage_condition'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Condition of the grain/ straw at storage'),
    ];

    // Add the harvest tab and fields to the form.
    $form['trailer'] = $trailer;

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
    array_push(
      $field_keys,
      'number_of_bales',
      'tare',
      'gross_weight',
      'nett_weight',
      'moisture_content',
    );
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
        [
          'key' => 'grain_sample_number',
          'label' => $this->t('Grain sample number'),
        ],
        [
          'key' => 'storage_condition',
          'label' => $this->t('Condition of grain/ straw at storage'),
        ],
      ]
    );
    return parent::prepareNotes($note_fields, $form_state);
  }

}
