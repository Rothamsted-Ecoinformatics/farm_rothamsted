<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_quick\Plugin\QuickForm;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\farm_quick\Attribute\QuickForm;
use Drupal\farm_quick\Traits\QuickLogTrait;

/**
 * Harvest quick form.
 */
#[QuickForm(
  id: 'combine_harvest',
  label: new TranslatableMarkup('Harvest (Combine and Forage Harvesters)'),
  description: new TranslatableMarkup('Create combine harvest records.'),
  helpText: new TranslatableMarkup('Use this form to record combine harvest records.'),
  permissions: [
    'create harvest log',
  ],
)]
class QuickCombineHarvest extends QuickExperimentFormBase {

  use QuickLogTrait;

  /**
   * {@inheritdoc}
   */
  protected $logType = 'harvest';

  /**
   * {@inheritdoc}
   */
  protected $parentLogCategoryName = 'Combine harvest categories';

  /**
   * {@inheritdoc}
   */
  protected $tractorField = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $machineryEquipmentTypes = ['Harvest Equipment'];

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?string $id = NULL) {
    $form = parent::buildForm($form, $form_state);

    // Change the tractor field to load Combine and Forage Harvester equipment.
    $combine_options = $this->getEquipmentOptions(['Combine and Forage Harvesters']);
    $form['setup']['equipment_wrapper']['tractor']['#title'] = new TranslatableMarkup('Combine/Forage Harvester');
    $form['setup']['equipment_wrapper']['tractor']['#description'] = new TranslatableMarkup('Select the combine or forage harvester used for this operation. You can expand this list by assigning Equipment Assets as “Combine and Forage Harvesters".');
    $form['setup']['equipment_wrapper']['tractor']['#options'] = $combine_options;

    // Harvest data tab.
    $harvest = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Harvest Data'),
      '#group' => 'tabs',
      '#weight' => 0,
    ];

    $harvest['harvest_lot_number'] = [
      '#type' => 'textfield',
      '#title' => new TranslatableMarkup('Harvest lot number'),
      '#description' => new TranslatableMarkup('The RRES harvest number, where applicable.'),
    ];

    // Common trailer weight units.
    // Copied from QuickTrailerHarvest for the yield estimate quantity.
    $trailer_weight_units = [
      't' => 'tonnes',
      'kg' => 'kilogrammes',
    ];

    // Machine yield estimate.
    $harvest['machine_yield_estimate'] = $this->buildQuantityField([
      'title' => new TranslatableMarkup('Machine yield estimate'),
      'description' => new TranslatableMarkup('The machine yield estimate as produced by the combine or forage harvester.'),
      'measure' => ['#value' => 'weight'],
      'units' => ['#options' => $trailer_weight_units],
    ]);

    // Harvest form.
    $harvest['operation']['harvest_form'] = [
      '#type' => 'managed_file',
      '#title' => new TranslatableMarkup('Harvest form'),
      '#description' => new TranslatableMarkup('Please upload the harvest form where relevant for experiments.'),
      '#upload_location' => $this->getFileUploadLocation('log', $this->logType, 'file'),
      '#upload_validators' => [
        'FileExtension' => [
          'extensions' => self::$validFileExtensions,
        ],
      ],
      '#multiple' => TRUE,
      '#extended' => TRUE,
    ];

    // Digital harvest records.
    $harvest['operation']['digital_harvest_records'] = [
      '#type' => 'managed_file',
      '#title' => new TranslatableMarkup('Digital harvest record(s)'),
      '#description' => new TranslatableMarkup('Please upload any digital records associated with this harvest (yields, crop samples, etc).'),
      '#upload_location' => $this->getFileUploadLocation('log', $this->logType, 'file'),
      '#upload_validators' => [
        'FileExtension' => [
          'extensions' => self::$validFileExtensions,
        ],
      ],
      '#multiple' => TRUE,
      '#extended' => TRUE,
    ];

    // Add the harvest tab and fields to the form.
    $form['harvest'] = $harvest;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareLog(array $form, FormStateInterface $form_state): array {
    $log = parent::prepareLog($form, $form_state);

    // Harvest lot number.
    $log['lot_number'] = $form_state->getValue('harvest_lot_number');

    return $log;
  }

  /**
   * {@inheritdoc}
   */
  protected function getLogName(array $form, FormStateInterface $form_state): string {
    return 'Harvest (Combine)';
  }

  /**
   * {@inheritdoc}
   */
  protected function getFileIds(array $field_keys, FormStateInterface $form_state) {
    $field_keys[] = 'harvest_form';
    $field_keys[] = 'digital_harvest_records';
    return parent::getImageIds($field_keys, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getQuantities(array $field_keys, FormStateInterface $form_state): array {
    array_push(
      $field_keys,
      'machine_yield_estimate',
    );
    return parent::getQuantities($field_keys, $form_state);
  }

}
