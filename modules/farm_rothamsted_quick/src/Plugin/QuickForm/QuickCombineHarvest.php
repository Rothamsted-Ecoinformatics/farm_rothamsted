<?php

namespace Drupal\farm_rothamsted_quick\Plugin\QuickForm;

use Drupal\Core\Form\FormStateInterface;
use Drupal\farm_quick\Traits\QuickLogTrait;

/**
 * Harvest quick form.
 *
 * @QuickForm(
 *   id = "combine_harvest",
 *   label = @Translation("Harvest (Combine and Forage Harvesters)"),
 *   description = @Translation("Create combine harvest records."),
 *   helpText = @Translation("Use this form to record combine harvest records."),
 *   permissions = {
 *     "create harvest log",
 *   }
 * )
 */
class QuickCombineHarvest extends QuickExperimentFormBase {

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
  public function buildForm(array $form, FormStateInterface $form_state, string $id = NULL) {
    $form = parent::buildForm($form, $form_state);

    // Change the tractor field to load Combine and Forage Harvester equipment.
    $combine_options = $this->getGroupMemberOptions(['Combine and Forage Harvesters'], ['equipment']);
    $form['setup']['equipment_wrapper']['tractor']['#title'] = $this->t('Combine/Forage Harvester');
    $form['setup']['equipment_wrapper']['tractor']['#description'] = $this->t('Select the combine or forage harvester used for this operation. You can expand this list by adding equipment to the group “Combine and Forage Harvesters.');
    $form['setup']['equipment_wrapper']['tractor']['#options'] = $combine_options;

    // Harvest data tab.
    $harvest = [
      '#type' => 'details',
      '#title' => $this->t('Harvest Data'),
      '#group' => 'tabs',
      '#weight' => 0,
    ];

    $harvest['harvest_lot_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Harvest lot number'),
      '#description' => $this->t('The RRES harvest number, where applicable.'),
    ];

    // Common trailer weight units.
    // Copied from QuickTrailerHarvest for the yield estimate quantity.
    $trailer_weight_units = [
      't' => 'tonnes',
      'kg' => 'kilogrammes',
    ];

    // Machine yield estimate.
    $harvest['machine_yield_estimate'] = $this->buildQuantityField([
      'title' => $this->t('Machine yield estimate'),
      'description' => $this->t('The machine yield estimate as produced by the combine or forage harvester.'),
      'measure' => ['#value' => 'weight'],
      'units' => ['#options' => $trailer_weight_units],
    ]);

    // Harvest form.
    $harvest['operation']['harvest_form'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Harvest form'),
      '#description' => $this->t('Please upload the harvest form where relevant for experiments.'),
      '#upload_location' => $this->getFileUploadLocation('log', $this->logType, 'file'),
      '#upload_validators' => [
        'file_validate_extensions' => self::$validFileExtensions,
      ],
      '#multiple' => TRUE,
      '#extended' => TRUE,
    ];

    // Digital harvest records.
    $harvest['operation']['digital_harvest_records'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Digital harvest record(s)'),
      '#description' => $this->t('Please upload any digital records associated with this harvest (yields, crop samples, etc).'),
      '#upload_location' => $this->getFileUploadLocation('log', $this->logType, 'file'),
      '#upload_validators' => [
        'file_validate_extensions' => self::$validFileExtensions,
      ],
      '#multiple' => TRUE,
      '#extended' => TRUE,
    ];

    // Add the harvest tab and fields to the form.
    $form['harvest'] = $harvest;

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

  /**
   * {@inheritdoc}
   */
  protected function prepareNotes(array $note_fields, FormStateInterface $form_state): array {
    // Prepend additional note fields.
    array_unshift(
      $note_fields,
      ...[
        [
          'key' => 'deviations',
          'label' => $this->t('Experimental Deviations'),
        ],
      ]
    );
    return parent::prepareNotes($note_fields, $form_state);
  }

}
