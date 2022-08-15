<?php

namespace Drupal\farm_rothamsted_quick\Plugin\QuickForm;

use Drupal\asset\Entity\AssetInterface;
use Drupal\Component\Utility\NestedArray;
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
      '#default_value' => $this->defaultValues['notes']['Type of harvest'] ?? NULL,
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

    // Trailer load count.
    $trailer_count = range(1, 10);
    $trailer['trailer_load_count'] = [
      '#type' => 'select',
      '#title' => $this->t('How many trailer loads?'),
      '#options' => array_combine($trailer_count, $trailer_count),
      '#default_value' => 1,
      '#ajax' => [
        'callback' => [$this, 'trailerLoadsCallback'],
        'event' => 'change',
        'wrapper' => 'farm-rothamsted-trailer-loads',
      ],
    ];

    // Container for trailer load weights.
    $trailer['trailer_loads'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#attributes' => ['id' => 'farm-rothamsted-trailer-loads'],
    ];
    $trailer_load_count = $form_state->get('trailer_load_count') ?? 1;
    if (($trigger = $form_state->getTriggeringElement()) && NestedArray::getValue($trigger['#array_parents'], [1]) == 'trailer_load_count') {
      $trailer_load_count = (int) $trigger['#value'];
    }
    $form_state->set('trailer_load_count', $trailer_load_count);
    for ($i = 0; $i < $trailer_load_count; $i++) {

      // Trailer weight. Allow the user to select either Gross or Nett weight.
      $trailer['trailer_loads'][$i]['weight'] = $this->buildQuantityField([
        'title' => $this->t('Trailer @count weight', ['@count' => $i + 1]),
        'description' => $this->t('The weight of the trailer + harvested grain, as measured on the scales.'),
        'measure' => ['#value' => 'weight'],
        'units' => ['#options' => $trailer_weight_units],
      ]);
      $trailer['trailer_loads'][$i]['weight']['value']['#states'] = [
        'required' => [
          ':input[name="type_of_harvest"]' => ['value' => 'Combinable crops (incl. sugar beet)'],
        ],
      ];

      // Weight label options.
      $weight_label_options = [
        'Gross weight',
        'Nett weight',
      ];
      $trailer['trailer_loads'][$i]['weight']['label'] = [
        '#type' => 'select',
        '#options' => array_combine($weight_label_options, $weight_label_options),
      ];
    }

    // Moisture content.
    $trailer['moisture_wrapper'] = $this->buildInlineWrapper();
    $trailer['moisture_wrapper']['moisture_content'] = $this->buildQuantityField([
      'title' => $this->t('Moisture content'),
      'description' => $this->t('The moisture content of the grain at the harvest.'),
      'measure' => ['#value' => 'ratio'],
      'units' => ['#value' => '%'],
    ]);
    $trailer['moisture_wrapper']['moisture_content']['value']['#states'] = [
      'required' => [
        ':input[name="type_of_harvest"]' => ['value' => 'Combinable crops (incl. sugar beet)'],
      ],
    ];
    $trailer['moisture_wrapper']['moisture_time'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Moisture content time'),
      '#description' => $this->t('The time the moisture content was taken.'),
      '#date_date_element' => 'none',
      '#date_time_element' => 'time',
      '#attributes' => ['step' => 60],
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
   * Trailer loads ajax callback.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The trailer loads render array.
   */
  public function trailerLoadsCallback(array &$form, FormStateInterface $form_state) {
    return $form['trailer']['trailer_loads'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Validate trailer load weights.
    $weight_units = NULL;
    $trailer_load_count = $form_state->get('trailer_load_count') ?? 0;
    for ($i = 0; $i < $trailer_load_count; $i++) {
      if ($trailer_weight = $form_state->getValue(['trailer_loads', $i, 'weight'])) {

        // Init weight units to the first trailer weight units.
        $weight_units = $weight_units ?? $trailer_weight['units'];

        // Ensure all weight units are the same.
        if ($trailer_weight['units'] != $weight_units) {
          $form_state->setErrorByName("trailer_loads][$i][weight][units", $this->t('All trailer weights must be the same units.'));
        }

        // Ensure gross weights have a tare weight.
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
      'tare',
    );
    $quantities = parent::getQuantities($field_keys, $form_state);

    // Compute the trailer nett weight.
    $total_nett_weight = [
      'label' => 'Nett weight',
      'measure' => 'weight',
      'units' => NULL,
      'value' => 0,
    ];
    $trailer_load_count = $form_state->get('trailer_load_count') ?? 0;
    for ($i = 0; $i < $trailer_load_count; $i++) {
      if (($trailer_weight = $form_state->getValue(['trailer_loads', $i, 'weight'])) && is_numeric($trailer_weight['value'])) {

        $trailer_count = 'Trailer ' . ($i + 1);

        // If a Nett weight is provided, include without further processing.
        if ($trailer_weight['label'] == 'Nett weight') {

          // Update the label and include the quantity.
          $trailer_weight['label'] = "$trailer_count " . $trailer_weight['label'];
          $quantities[] = $trailer_weight;

          // Increment the total nett weight.
          $total_nett_weight['units'] = $total_nett_weight['units'] ?? $trailer_weight['units'];
          $total_nett_weight['value'] += $trailer_weight['value'];
        }

        // Else compute the individual nett weight from Gross and Tare weight.
        elseif ($trailer_weight['label'] == 'Gross weight') {

          // Only compute if Tare is provided. Validation should enforce this.
          if (($tare = $form_state->getValue('tare')) && is_numeric($tare['value'])) {

            // Update the label and include the gross weight quantity.
            $trailer_weight['label'] = "$trailer_count " . $trailer_weight['label'];
            $quantities[] = $trailer_weight;

            // Add to the total nett weight.
            $total_nett_weight['units'] = $total_nett_weight['units'] ?? $trailer_weight['units'];
            $total_nett_weight['value'] += $trailer_weight['value'] - $tare['value'];
          }
        }
      }
    }

    // Include the total nett weight.
    if (!empty($total_nett_weight['value'])) {
      $quantities[] = $total_nett_weight;
    }

    // Moisture content quantity.
    if (($moisture_content = $form_state->getValue('moisture_content')) && is_numeric($moisture_content['value'])) {

      // Append the moisture content time, if provided.
      if ($moisture_time = $form_state->getValue('moisture_time')) {
        $time = $moisture_time->format('H:i');
        $moisture_content['label'] .= " $time";
      }
      $quantities[] = $moisture_content;
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
