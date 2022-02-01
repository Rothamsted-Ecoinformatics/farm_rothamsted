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
  protected $logType = 'seeding';

  /**
   * {@inheritdoc}
   */
  protected $tractorField = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $machineryGroupNames = ['Drilling Equipment'];

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Add to the operation tab.
    $operation = $form['operation'];

    // Drilling tab.
    $drilling = [
      '#type' => 'details',
      '#title' => $this->t('Drilling'),
      '#group' => 'tabs',
      '#weight' => 0,
    ];

    // Crop and variety wrapper.
    $drilling['crop'] = $this->buildInlineWrapper();

    // Crop types.
    // @todo Select parent plant_type term.
    $crop_types = [
      'Grass',
      'Spuds',
      'Wheat',
      'Corn',
    ];
    $crop_types_options = array_combine($crop_types, $crop_types);

    $drilling['crop']['crops'] = [
      '#type' => 'select',
      '#title' => $this->t('Crop(s)'),
      '#description' => $this->t('The crop(s) being drilled.'),
      '#options' => $crop_types_options,
      '#required' => TRUE,
    ];

    // Crop variety types.
    // @todo Select child plant_type term.
    $crop_variety_types = [
      'Red',
      'Green',
      'Blue',
      'Orange',
    ];
    $crop_variety_types_options = array_combine($crop_variety_types, $crop_variety_types);

    $drilling['crop']['crop_variety'] = [
      '#type' => 'select',
      '#title' => $this->t('Variety(s)'),
      '#description' => $this->t('The variety(s) being planted.'),
      '#options' => $crop_variety_types_options,
      '#multiple' => TRUE,
      '#required' => TRUE,
    ];

    // Target plant population units options.
    $target_plant_population_units_options = [
      'plants/ m2' => 'plants/ m2',
      '%' => '%',
    ];

    // Target plant population.
    // @todo Target plant population ??
    $drilling['target_plant_population'] = $this->buildQuantityUnitsElement([
      '#type' => 'number',
      '#title' => $this->t('Target plant population'),
      '#description' => $this->t('The target population for plant establishment after drilling.'),
      '#required' => FALSE,
      '#units_type' => 'select',
      '#units_options' => $target_plant_population_units_options,
    ]);

    // Establishment average units options.
    $establishment_average_units_options = [
      'plants/ m2' => 'plants/ m2',
      '%' => '%',
    ];

    // Establishment average.
    $drilling['establishment_average'] = $this->buildQuantityUnitsElement([
      '#type' => 'number',
      '#title' => $this->t('Establishment average'),
      '#description' => $this->t('The estimated plant establishment after drilling as a percentage. This is usually based on previous field records over the last 2- 5 years.'),
      '#required' => FALSE,
      '#units_type' => 'select',
      '#units_options' => $establishment_average_units_options,
    ]);

    // Germination rate.
    $drilling['germination_rate'] = [
      '#type' => 'number',
      '#title' => $this->t('Germination rate'),
      '#description' => $this->t('The germination rate of the seed batch, measured by placing 50 to 100 seeds in a sealed tupperware box lined with wet kitchen roll and counting the number of seeds germinated after 10 - 14 days.'),
      '#field_suffix' => $this->t('%'),
      '#required' => FALSE,
    ];

    // Thousand grain weight.
    $drilling['thousand_grain_weight'] = [
      '#type' => 'number',
      '#title' => $this->t('Thousand grain weight (TGW)'),
      '#description' => $this->t('The average weight of 1,000 grains.'),
      '#field_suffix' => $this->t('g'),
      '#required' => FALSE,
    ];

    // Seed rate units options.
    $seed_rate_units_options = [
      'seeds/ m2' => 'seeds/ m2',
      'units/ha' => 'units/ha',
    ];

    // Seed rate.
    $drilling['seed_rate'] = $this->buildQuantityUnitsElement([
      '#type' => 'number',
      '#title' => $this->t('Seed rate'),
      '#description' => $this->t('The number of seeds drilled per unit area. This is an agronomic decision based on the crop, the season and the growing conditions.'),
      '#required' => TRUE,
      '#units_type' => 'select',
      '#units_options' => $seed_rate_units_options,
    ]);

    // Drilling rate.
    $drilling['drilling_rate'] = [
      '#type' => 'number',
      '#title' => $this->t('Drilling rate'),
      '#description' => $this->t('The volume of seed drilled per unit area. This information must be provided as it is essential information for scientists wanting to analyse the crop data.'),
      '#field_suffix' => $this->t('kg/ha'),
      '#required' => TRUE,
    ];

    // Seed volume.
    $drilling['seed_volume'] = [
      '#type' => 'number',
      '#title' => $this->t('Seed volume'),
      '#description' => $this->t('The total amount of seed needed for a given area.'),
      '#field_suffix' => $this->t('kg'),
      '#required' => TRUE,
    ];

    // Drilling depth.
    $drilling['drilling_depth'] = [
      '#type' => 'number',
      '#title' => $this->t('Drilling depth'),
      '#description' => $this->t('The estimate of the depth at which the seed was drilled. It is important to take this info account when reviewing establishment avarages.'),
      '#field_suffix' => $this->t('cm'),
      '#required' => FALSE,
    ];

    // Seed dressing(s) options.
    // @todo Pull from materials taxonomy, child term "Seed Dressings".
    $seed_dressing_options = [
      'top' => 'top',
      'deep' => 'deep',
      'cross' => 'cross',
    ];

    // Seed dressing(s).
    $drilling['seed_dressings'] = [
      '#type' => 'select',
      '#title' => $this->t('Seed dressing(s)'),
      '#description' => $this->t('Please record the seed dressings applied either by the farm or by the supplier.'),
      '#options' => $seed_dressing_options,
      '#multiple' => TRUE,
      '#required' => FALSE,
    ];

    // @todo starter fertiliser

    // Product options.
    $product_options = [
      'top' => 'top',
      'deep' => 'deep',
      'cross' => 'cross',
    ];

    // Product.
    $drilling['product'] = [
      '#type' => 'select',
      '#title' => $this->t('Product'),
      '#description' => $this->t('The product used as a starter fertiliser.'),
      '#options' => $product_options,
      '#required' => FALSE,
    ];

    // Product application rate units options.
    $product_application_rate_units_options = [
      'l/ha' => 'l/ha',
      'kg/ha' => 'kg/ha',
      'ml/ha' => 'ml/ha',
      'g/ha' => 'g/ha',
      't/ha' => 't/ha',
    ];

    // Product application rate.
    $drilling['product_application_rate'] = $this->buildQuantityUnitsElement([
      '#type' => 'number',
      '#title' => $this->t('Product application rate'),
      '#description' => $this->t('The volume of product per unit that needs to be applied in order to achieve the desired nutrient rate(s).'),
      '#required' => FALSE,
      '#units_type' => 'select',
      '#units_options' => $product_application_rate_units_options,
    ]);

    // Seed lineage options.
    $seed_lineage_options = [
      'red' => 'red',
      'blue' => 'blue',
      'green' => 'green',
    ];

    // Product.
    $drilling['seed_lineage'] = [
      '#type' => 'select',
      '#title' => $this->t('Seed lineage'),
      '#description' => $this->t('The plant asset(s) which the seed came from.'),
      '#options' => $seed_lineage_options,
      '#multiple' => TRUE,
      '#required' => FALSE,
    ];

    // Add the drilling tab and fields to the form.
    $form['drilling'] = $drilling;

    // Seed lot wrapper.
    $operation['seed_lots'] = $this->buildInlineWrapper();

    // Farm seed lot.
    $operation['seed_lots']['farm_seed_lot'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Farm seed lot'),
      '#description' => $this->t('The seed lot number assigned by the farm.'),
      '#required' => TRUE,
      '#size' => 30,
    ];

    // Supplier seed lot.
    $operation['seed_lots']['supplier_seed_lot'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Supplier seed lot'),
      '#description' => $this->t('The seed lot number provided by the supplier, if available.'),
      '#required' => FALSE,
      '#size' => 30,
    ];

    // Seed labels.
    $operation['seed_labels'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Seed labels'),
      '#description' => $this->t('Photograph(s) of the seed label taken prior to drilling or confirm the right seed batch and variety was used.'),
      '#upload_location' => $this->getFileUploadLocation('log', $this->logType, 'image'),
      '#upload_validators' => [
        'file_validate_extensions' => self::$validImageExtensions,
      ],
      '#multiple' => TRUE,
      '#extended' => TRUE,
      '#required' => TRUE,
    ];

    // Add the operation tab and fields to the form.
    $form['operation'] = $operation;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getImageIds(array $field_keys, FormStateInterface $form_state) {
    $field_keys[] = 'seed_labels';
    return parent::getImageIds($field_keys, $form_state);
  }

}
