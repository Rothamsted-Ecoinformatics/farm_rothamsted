<?php

namespace Drupal\farm_rothamsted_quick\Plugin\QuickForm;

use Drupal\asset\Entity\AssetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\farm_quick\Traits\QuickLogTrait;

/**
 * Harvest quick form.
 *
 * @QuickForm(
 *   id = "trailer_harvest",
 *   label = @Translation("Harvest (Trailer/ Bale Weights)"),
 *   description = @Translation("Create trailer harvest records."),
 *   helpText = @Translation("Use this form to record trailer harvest records."),
 *   permissions = {
 *     "create harvest log",
 *   }
 * )
 */
class QuickTrailerHarvest extends QuickExperimentFormBase {

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
  protected $machineryGroupNames = ['Harvest Equipment'];

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

    // Grass/Straw Bales tab.
    $bales = [
      '#type' => 'details',
      '#title' => $this->t('Grass/Straw Bales'),
      '#group' => 'tabs',
      '#weight' => 0,
    ];

    // Total number of bales.
    $bales_units_options = $this->getChildTermOptionsByName('unit', 'Grass/Straw Bale Types', 1);
    $bales['total_number_bales'] = $this->buildQuantityField([
      'title' => $this->t('Total number of bales'),
      'description' => $this->t('Please give the total number of bales from this harvest and state if the bale is wrapped or not.'),
      'measure' => ['#value' => 'count'],
      'units' => ['#options' => $bales_units_options],
    ]);

    // Bale wrapped, total number of bales label.
    $wrapped_options = [
      'Wrapped bales',
      'Unwrapped bales',
    ];
    $bales['total_number_bales']['label'] = [
      '#type' => 'select',
      '#options' => array_combine($wrapped_options, $wrapped_options),
    ];

    $form['bales'] = $bales;

    // Trailer Load tab.
    $trailer = [
      '#type' => 'details',
      '#title' => $this->t('Trailer Load'),
      '#group' => 'tabs',
      '#weight' => 1,
    ];

    // Common trailer weight units.
    $trailer_weight_units = [
      't' => 'tonnes',
      'kg' => 'kilogrammes',
    ];

    // Tare.
    $trailer['tare'] = $this->buildQuantityField([
      'title' => $this->t('Trailer tare'),
      'description' => $this->t('The weight of the trailer, as measured on the scales.'),
      'measure' => ['#value' => 'weight'],
      'units' => ['#options' => $trailer_weight_units],
    ]);

    // Trailer weight. Allow the user to select either Gross or Nett weight.
    $trailer['weight'] = $this->buildQuantityField([
      'title' => $this->t('Trailer weight'),
      'description' => $this->t('The weight of the trailer + harvested grain, as measured on the scales.'),
      'measure' => ['#value' => 'weight'],
      'units' => ['#options' => $trailer_weight_units],
    ]);
    $trailer['weight']['value']['#states'] = [
      'required' => [
        ':input[name="type_of_harvest"]' => ['value' => 'Combinable crops (incl. sugar beet)'],
      ],
    ];

    // Weight label options.
    $weight_label_options = [
      'Gross weight',
      'Nett weight',
    ];
    $trailer['weight']['label'] = [
      '#type' => 'select',
      '#options' => array_combine($weight_label_options, $weight_label_options),
    ];

    // Moisture content.
    $trailer['moisture_content'] = $this->buildQuantityField([
      'title' => $this->t('Moisture content'),
      'description' => $this->t('The moisture content of the grain at the harvest.'),
      'measure' => ['#value' => 'ratio'],
      'units' => ['#value' => '%'],
    ]);
    $trailer['moisture_content']['value']['#states'] = [
      'required' => [
        ':input[name="type_of_harvest"]' => ['value' => 'Combinable crops (incl. sugar beet)'],
      ],
    ];

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

    // Storage locations.
    $storage_locations = $this->entityTypeManager->getStorage('asset')->loadByProperties([
      'type' => 'structure',
      'structure_type' => 'storage_location',
      'status' => 'active',
    ]);
    $storage_location_options = array_map(function (AssetInterface $asset) {
      return $asset->label();
    }, $storage_locations);
    natsort($storage_location_options);
    $form['operation']['storage_location'] = [
      '#type' => 'select',
      '#title' => $this->t('Storage location'),
      '#description' => $this->t('Please select the location where the grain/ straw is being stored. This list can be expanded by creating new Storage Location structure assets.'),
      '#options' => $storage_location_options,
      '#required' => TRUE,
    ];

    // Experimental deviations.
    $form['job_status']['deviations'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Experimental Deviations'),
      '#description' => $this->t('Please describe any deviations from the experiment plan where relevant. Please include anything that might affect the results of the experiment such as spraying, equipment and application errors.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Ensure a tare is provided for gross weights.
    if ($trailer_weight = $form_state->getValue('weight')) {
      if ($trailer_weight['label'] == 'Gross weight' && is_numeric($trailer_weight['value'])) {

        // Get the tare weight.
        if ($tare = $form_state->getValue('tare')) {

          // Ensure the tare is provided.
          if (!is_numeric($tare['value'])) {
            $form_state->setErrorByName('tare', $this->t('A tare weight must be provided for gross trailer weights.'));
          }

          // Ensure the tare units match.
          if ($tare['units'] != $trailer_weight['units']) {
            $form_state->setErrorByName('tare', $this->t('The tare units must match the trailer weight units.'));
          }

          // Ensure the tare is less than the trailer weight.
          if ($tare['value'] >= $trailer_weight['value']) {
            $form_state->setErrorByName('tare', $this->t('The tare weight must be less than the trailer weight.'));
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareLog(array $form, FormStateInterface $form_state): array {
    $log = parent::prepareLog($form, $form_state);

    // Include the storage location.
    $log['storage_location'] = $form_state->getValue('storage_location');

    return $log;
  }

  /**
   * {@inheritdoc}
   */
  protected function getLogName(array $form, FormStateInterface $form_state): string {
    return 'Harvest (Trailer and Bale Weights)';
  }

  /**
   * {@inheritdoc}
   */
  protected function getQuantities(array $field_keys, FormStateInterface $form_state): array {
    array_push(
      $field_keys,
      'total_number_bales',
      'moisture_content',
      'tare',
    );
    $quantities = parent::getQuantities($field_keys, $form_state);

    // Compute the trailer nett weight.
    if (($trailer_weight = $form_state->getValue('weight')) && is_numeric($trailer_weight['value'])) {

      // If a Nett weight is provided, include without further processing.
      if ($trailer_weight['label'] == 'Nett weight') {
        $quantities[] = $trailer_weight;
      }

      // Else compute the Nett weight from the Gross weight and Tare weight.
      else if ($trailer_weight['label'] == 'Gross weight') {

        // Only compute if the Tare is provided. Validation should enforce this.
        if (($tare = $form_state->getValue('tare')) && is_numeric($tare['value'])) {

          // Include the gross weight quantity.
          $quantities[] = $trailer_weight;

          // Build nett_weight quantity.
          $quantities[] = [
            'label' => 'Nett weight',
            'measure' => 'weight',
            'units' => $trailer_weight['units'],
            'value' => $trailer_weight['value'] - $tare['value'],
          ];
        }
      }
    }

    return $quantities;
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
        [
          'key' => 'deviations',
          'label' => $this->t('Experimental Deviations'),
        ],
      ]
    );
    return parent::prepareNotes($note_fields, $form_state);
  }

}
