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
  protected $tractorField = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $machineryGroupNames = ['Drilling Equipment'];

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $weight = 200;
    $form = parent::buildForm($form, $form_state);

    // @todo Select parent plant_type term.
    // @todo Select child plant_type term.

    // Crop types.
    $crop_types = [
      'Grass',
      'Spuds',
      'Wheat',
      'Corn',
    ];
    $crop_types_options = array_combine($crop_types, $crop_types);

    $form['crops'] = [
      '#type' => 'select',
      '#title' => $this->t('Crop(s)'),
      '#options' => $crop_types_options,
      '#required' => TRUE,
      '#description' => $this->t('The crop(s) being drilled.'),
      '#weight' => ++$weight,
    ];

    // Crop variety types.
    $crop_veriety_types = [
      'Red',
      'Green',
      'Blue',
      'Orange',
    ];
    $crop_veriety_types_options = array_combine($crop_veriety_types, $crop_veriety_types);

    $form['crop_veriety'] = [
      '#type' => 'select',
      '#title' => $this->t('Variety(s)'),
      '#options' => $crop_veriety_types_options,
      '#required' => TRUE,
      '#description' => $this->t('The variety(s) being planted.'),
      '#weight' => ++$weight,
    ];

    // Tractor (on base form)
    $form['tractor']['#weight'] = ++$weight;

    // Machinery checkboxes - required.
    $form['machinery']['#required'] = TRUE;
    $form['machinery']['#weight'] = ++$weight;

    // @todo Target plant population ??

    // Target plant population.
    $form['target_plant_population'] = [
      '#type' => 'number',
      '#title' => $this->t('Target plant population'),
      '#required' => FALSE,
      '#description' => $this->t('The target population for plant establishment after drilling.'),
      '#weight' => ++$weight,
    ];

    // Target plant population units options.
    $target_plant_population_units_options = [
      'plants/ m2' => 'plants/ m2',
      '%' => '%',
    ];

    // Target plant population units.
    $form['target_plant_population_units'] = [
      '#type' => 'radios',
      '#title' => $this->t('Establishment avarage units'),
      '#options' => $target_plant_population_units_options,
      '#weight' => ++$weight,
    ];

    // Establishment avarage.
    $form['establishment_avarage'] = [
      '#type' => 'number',
      '#title' => $this->t('Establishment avarage'),
      '#required' => FALSE,
      '#description' => $this->t('The estimated plant establishment after drilling as a percentage. This is usually based on previous field records over the last 2- 5 years.'),
      '#weight' => ++$weight,
    ];

    // Establishment avarage units options.
    $establishment_avarage_units_options = [
      'plants/ m2' => 'plants/ m2',
      '%' => '%',
    ];

    // Establishment avarage units.
    $form['establishment_avarage_units'] = [
      '#type' => 'radios',
      '#title' => $this->t('Establishment avarage units'),
      '#options' => $establishment_avarage_units_options,
      '#weight' => ++$weight,
    ];

    // Germination rate.
    $form['germination_rate'] = [
      '#type' => 'number',
      '#title' => $this->t('Germination rate'),
      '#field_suffix' => $this->t('%'),
      '#required' => FALSE,
      '#description' => $this->t('The germination rate of the seed batch, measured by placing 50 to 100 seeds in a sealed tupperware box lined with wet kitchen roll and counting the number of seeds germinated after 10 - 14 days.'),
      '#weight' => ++$weight,
    ];

    // Thousand grain weight.
    $form['thousand_grain_weight'] = [
      '#type' => 'number',
      '#title' => $this->t('Thousand grain weight (TGW)'),
      '#field_suffix' => $this->t('g'),
      '#required' => FALSE,
      '#description' => $this->t('The avarage weight of 1,000 grains.'),
      '#weight' => ++$weight,
    ];

    // Seed rate.
    $form['seed_rate'] = [
      '#type' => 'number',
      '#title' => $this->t('Seed rate'),
      '#required' => TRUE,
      '#description' => $this->t('The number of seeds drilled per unit area. This is an agronomic decision based on the crop, the season and the growing conditions.'),
      '#weight' => ++$weight,
    ];

    // Seed rate units options.
    $seed_rate_units_options = [
      'seeds/ m2' => 'seeds/ m2',
      'units/ha' => 'units/ha',
    ];

    // Seed rate units.
    $form['seed_rate_units'] = [
      '#type' => 'radios',
      '#title' => $this->t('Seed rate units'),
      '#options' => $seed_rate_units_options,
      '#required' => TRUE,
      '#weight' => ++$weight,
    ];

    // Drilling rate.
    $form['drilling_rate'] = [
      '#type' => 'number',
      '#title' => $this->t('Drilling rate'),
      '#field_suffix' => $this->t('kg/ha'),
      '#required' => TRUE,
      '#description' => $this->t('The volume of seed drilled per unit area. This information must be provided as it is essential information for scientists wanting to analyse the crop data.'),
      '#weight' => ++$weight,
    ];

    // Seed volume.
    $form['seed_volume'] = [
      '#type' => 'number',
      '#title' => $this->t('Seed volume'),
      '#field_suffix' => $this->t('kg'),
      '#required' => TRUE,
      '#description' => $this->t('The total amount of seed needed for a given area.'),
      '#weight' => ++$weight,
    ];

    // Drilling depth.
    $form['drilling_depth'] = [
      '#type' => 'number',
      '#title' => $this->t('Drilling depth'),
      '#field_suffix' => $this->t('cm'),
      '#required' => FALSE,
      '#description' => $this->t('The estimate of the depth at which the seed was drilled. It is important to take this info account when reviewing establishment avarages.'),
      '#weight' => ++$weight,
    ];

    // Seed dressing(s) options.
    $seed_dressing_options = [
      'top' => 'top',
      'deep' => 'deep',
      'cross' => 'cross',
    ];

    // Seed dressing(s).
    $form['seed_dressings'] = [
      '#type' => 'select',
      '#title' => $this->t('Seed dressing(s)'),
      '#options' => $seed_dressing_options,
      '#required' => FALSE,
      '#description' => $this->t('Please record the seed dressings applied either by the farm or by the supplyer.'),
      '#weight' => ++$weight,
    ];

    // @todo starter fertiliser

    // Product options.
    $product_options = [
      'top' => 'top',
      'deep' => 'deep',
      'cross' => 'cross',
    ];

    // Product.
    $form['product'] = [
      '#type' => 'select',
      '#title' => $this->t('Product'),
      '#options' => $product_options,
      '#required' => FALSE,
      '#description' => $this->t('The product used as a starter fertiliser.'),
      '#weight' => ++$weight,
    ];

    // Product application rate.
    $form['product_application_rate'] = [
      '#type' => 'number',
      '#title' => $this->t('Product application rate'),
      '#required' => FALSE,
      '#description' => $this->t('The volume of product per unit that needs to be applied in order to achieve the desired nutrient rate(s).'),
      '#weight' => ++$weight,
    ];

    // Product application rate units options.
    $product_application_rate_units_options = [
      'l/ha' => 'l/ha',
      'kg/ha' => 'kg/ha',
      'ml/ha' => 'ml/ha',
      'g/ha' => 'g/ha',
      't/ha' => 't/ha',
    ];

    // Product application rate units.
    $form['product_application_rate_units'] = [
      '#type' => 'radios',
      '#title' => $this->t('Seed rate units'),
      '#options' => $product_application_rate_units_options,
      '#required' => FALSE,
      '#weight' => ++$weight,
    ];

    // Farm seed lot.
    $form['farm_seed_lot'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Farm seed lot'),
      '#required' => TRUE,
      '#description' => $this->t('The seed lot number assigned by the farm.'),
      '#weight' => ++$weight,
    ];

    // Supplier seed lot.
    $form['supplier_seed_lot'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Supplier seed lot'),
      '#required' => FALSE,
      '#description' => $this->t('The seed lot number provided by the supplier, if available.'),
      '#weight' => ++$weight,
    ];

    // Seed lineage options.
    $seed_lineage_options = [
      'red' => 'red',
      'blue' => 'blue',
      'green' => 'green',
    ];

    // Product.
    $form['seed_lineage'] = [
      '#type' => 'select',
      '#title' => $this->t('Seed lineage'),
      '#options' => $seed_lineage_options,
      '#required' => FALSE,
      '#description' => $this->t('The plant asset(s) which the seed came from.'),
      '#weight' => ++$weight,
    ];

    // Recommendation number.
    $form['supplier_seed_lot'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Recommendation number'),
      '#required' => FALSE,
      '#description' => $this->t('A recommendation or reference number from the argronomist or crop consultant.'),
      '#weight' => ++$weight,
    ];

    // Rcommendation files - file picker - optional.
    // @todo Determine the final file upload location.
    // @todo specify file types
    $form['recommendation_files'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Recommendation files'),
      '#upload_location' => 'private://quick',
      '#upload_validators' => [
        'file_validate_extensions' => ['pdf xls doc'],
      ],
      '#required' => FALSE,
      '#description' => $this->t('A pdf, word or excell file with the agronomist or crop consultant recommendations.'),
      '#weight' => ++$weight,
    ];

    // Scheduled by.
    $form['scheduled_by']['#weight'] = ++$weight;

    // Scheduled date and time.
    $form['date']['#weight'] = ++$weight;

    // Flag.
    $form['flag']['#weight'] = ++$weight;

    // Job status.
    $form['job_status']['#weight'] = ++$weight;

    // Operation start time and date - date time picker - required.
    $form['operation_start_time_and_date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Operation start time and date'),
      '#required' => TRUE,
      '#description' => $this->t('The start date and time of the spray operation.'),
      '#weight' => ++$weight,
    ];

    // Tractor hours start.
    $form['tractor_hours_start']['#weight'] = ++$weight;

    // Tractor hours end.
    $form['tractor_hours_end']['#weight'] = ++$weight;

    // Time taken.
    $form['time']['#weight'] = ++$weight;

    // Fuel use.
    $form['fuel_use']['#weight'] = ++$weight;

    // Fuel use units options.
    $fuel_use_units_options = [
      'l' => 'l',
      'gal' => 'gal',
    ];

    // Fuel use units.
    $form['fuel_use_units'] = [
      '#type' => 'radios',
      '#title' => $this->t('Fuel use units'),
      '#options' => $fuel_use_units_options,
      '#description' => $this->t('The Fuel use units.'),
      '#weight' => ++$weight,
    ];

    // Operator field.
    $form['users']['#weight'] = ++$weight;

    // Seed labels - file picker - optional.
    // @todo Determine the final file upload location.
    $form['seed_labels'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Seed labels'),
      '#upload_location' => 'private://quick',
      '#upload_validators' => [
        'file_validate_extensions' => ['jpg jpeg'],
      ],
      '#required' => TRUE,
      '#description' => $this->t('Photograph(s) of the seed label taken prior to drilling or confirm the right seed batch and variety was used.'),
      '#weight' => ++$weight,
    ];

    // Crop photograph(s) - file picker - optional.
    // @todo Determine the final file upload location.
    $form['crop_photographs'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Crop photograph(s)'),
      '#upload_location' => 'private://quick',
      '#upload_validators' => [
        'file_validate_extensions' => ['jpg jpeg'],
      ],
      '#description' => $this->t('A photograph of the crop, if applicable.'),
      '#weight' => ++$weight,
    ];

    // Photographs of paper record(s) - file picker - optional.
    // @todo Determine the final file upload location.
    $form['paper_records'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Photographs of paper record(s)'),
      '#upload_location' => 'private://quick',
      '#upload_validators' => [
        'file_validate_extensions' => ['jpg jpeg'],
      ],
      '#description' => $this->t('One or more photographs of any paper records, if applicable.'),
      '#weight' => ++$weight,
    ];

    // Equipment settings.
    $form['equipment_settings'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Equipment settings'),
      '#description' => $this->t('An option to include any notes on the specific equipment settings used.'),
      '#weight' => ++$weight,
    ];

    // Notes.
    $form['notes']['#weight'] = ++$weight;

    // Job status.
    $form['job_status']['#weight'] = ++$weight;

    $form['actions']['#weight'] = ++$weight;

    return $form;
  }

}
