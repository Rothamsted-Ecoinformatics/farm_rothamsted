<?php

namespace Drupal\farm_rothamsted\Plugin\QuickForm;

use Drupal\Core\Form\FormStateInterface;

/**
 * Fertiliser quick form.
 *
 * @QuickForm(
 *   id = "farm_rothamsted_fertiliser_quick_form",
 *   label = @Translation("Fertiliser, Compost and Manure"),
 *   description = @Translation("Create fertiliser records."),
 *   helpText = @Translation("Use this form to record feriliser records."),
 *   permissions = {
 *     "create input log",
 *   }
 * )
 */
class QuickFertiliser extends QuickExperimentFormBase {

  /**
   * {@inheritdoc}
   */
  protected $tractorField = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $machineryGroupNames = ['Fertiliser Equipment'];

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $weight = 1;
    $form = parent::buildForm($form, $form_state);

    // Machinery checkboxes - required.
    $form['machinery']['#required'] = TRUE;

    // Nutrient input.
    // @todo We need AJAX to populate multiple of these.
    $form['nutrient_input'] = [
      '#type' => 'details',
      '#title' => $this->t('Nutrient Input'),
      '#open' => FALSE,
      '#weight' => ++$weight,
    ];

    // Build product_type options.
    $product_type_options = $this->getTermOptions('material_type');

    // Product type - select - optional.
    $form['nutrient_input']['product_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Product type'),
      '#options' => $product_type_options,
      '#required' => TRUE,
      '#weight' => ++$weight,
    ];

    // Product - select - optional.
    $form['nutrient_input']['product'] = [
      '#type' => 'select',
      '#title' => $this->t('Product'),
      '#options' => $product_type_options,
      '#required' => TRUE,
      '#weight' => ++$weight,
    ];

    // Nutrient form placeholder.
    $form['nutrient_input']['nutrient'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nutrient'),
      '#placeholder' => $this->t('TBD'),
      '#required' => TRUE,
      '#weight' => ++$weight,
    ];

    // Nutrient content - text - optional.
    $form['nutrient_input']['nutrient_content'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nutrient content %'),
      '#weight' => ++$weight,
    ];

    // Nutrient application rate - number - required.
    $form['nutrient_input']['nutrient_application_rate'] = [
      '#type' => 'number',
      '#title' => $this->t('Nutrient application rate'),
      '#required' => TRUE,
      '#weight' => ++$weight,
    ];

    // Build application rate units options from units / spray taxonomy.
    $application_rate_units_options = $this->getChildTermOptions('unit', 'spray');

    // Nutrient application rate - select - required.
    $form['nutrient_input']['nutrient_application_rate_units'] = [
      '#type' => 'select',
      '#title' => $this->t('Nutrient application rate units'),
      '#options' => $application_rate_units_options,
      '#required' => TRUE,
      '#weight' => ++$weight,
    ];

    // Product application rate - number - required.
    $form['nutrient_input']['product_application_rate'] = [
      '#type' => 'number',
      '#title' => $this->t('Product application rate'),
      '#required' => TRUE,
      '#weight' => ++$weight,
    ];

    // Product application rate - select - required.
    $form['nutrient_input']['product_application_rate_units'] = [
      '#type' => 'select',
      '#title' => $this->t('Product application rate units'),
      '#options' => $application_rate_units_options,
      '#required' => TRUE,
      '#weight' => ++$weight,
    ];

    // Product volume - number - required.
    $form['nutrient_input']['product_volume'] = [
      '#type' => 'number',
      '#title' => $this->t('Product volume'),
      '#required' => TRUE,
      '#weight' => ++$weight,
    ];

    // Build volume units options from units / volume taxonomy.
    // @todo We need to specify the correct fuel units.
    // The volume units are not the same for every field.
    $application_volume_units_options = [];

    // Product volume units - select - required.
    $form['nutrient_input']['product_volume_units'] = [
      '#type' => 'select',
      '#title' => $this->t('Product volume units'),
      '#options' => $application_volume_units_options,
      '#required' => TRUE,
      '#weight' => ++$weight,
    ];

    // The following fields come after the tractor and machinery fields.
    $weight = 15;

    // Recommendation Number - text - optional.
    $form['recommendation_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Recommendation Number'),
      '#weight' => ++$weight,
    ];

    // Recommendation files - file picker - optional.
    // @todo Determine the final file upload location.
    $form['recommendation_files'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Recommendation files'),
      '#upload_location' => 'private://quick',
      '#upload_validators' => [
        'file_validate_extensions' => ['pdf doc docx csv xls xlsx'],
      ],
      '#weight' => ++$weight,
    ];

    // Build options from people who are managers or farm workers.
    $farm_staff_options = $this->getUserOptions(['farm_manager', 'farm_worker']);

    // Scheduled by - select - required.
    $form['scheduled_by'] = [
      '#type' => 'select',
      '#title' => $this->t('Scheduled by'),
      '#options' => $farm_staff_options,
      '#required' => TRUE,
      '#weight' => ++$weight,
    ];

    // Scheduled date and time - date time picker - required.
    $form['date']['#title'] = $this->t('Scheduled date and time');
    $form['date']['#date_time_element'] = 'time';
    $form['date']['#weight'] = ++$weight;

    // Build hazard options.
    // @todo Determine way to define hazard options. See issue #64.
    $hazard_options = [];

    // COSSH Hazard Assessments - checkboxes - required.
    $form['cossh_hazard_assessments'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('COSSH Hazard Assessments'),
      '#options' => $hazard_options,
      '#required' => TRUE,
      '#weight' => ++$weight,
    ];

    // In-field application records.
    // @todo We need AJAX to populate multiple of these.
    $form['application_record'] = [
      '#type' => 'details',
      '#title' => $this->t('In-field application record'),
      '#open' => FALSE,
      '#weight' => ++$weight,
    ];

    // Operation start time and date - date time picker - required.
    $form['application_record']['operation_start_time_and_date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Operation start time and date'),
      '#required' => TRUE,
      '#weight' => ++$weight,
    ];

    // Tractor hours (start) - number - required.
    $form['application_record']['tractor_hours_start'] = [
      '#type' => 'number',
      '#title' => $this->t('Tractor hours (start)'),
      '#required' => TRUE,
      '#weight' => ++$weight,
    ];

    // Tractor hours (end) - number - required.
    $form['application_record']['tractor_hours_end'] = [
      '#type' => 'number',
      '#title' => $this->t('Tractor hours (end)'),
      '#required' => TRUE,
      '#weight' => ++$weight,
    ];

    // Time taken - text - required.
    $form['application_record']['time_taken'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Time taken hh:mm'),
      '#required' => TRUE,
      '#weight' => ++$weight,
    ];

    // Fuel use - number - optional.
    $form['application_record']['fuel_use'] = [
      '#type' => 'number',
      '#title' => $this->t('Fuel use'),
      '#weight' => ++$weight,
    ];

    // Build fuel use units options from units / volume taxonomy.
    // @todo We need to specify the correct fuel units.
    // The volume units are not the same for every field.
    $fuel_use_units_options = [];

    // Fuel use units - select - optional.
    $form['application_record']['fuel_use_units'] = [
      '#type' => 'select',
      '#title' => $this->t('Fuel use units'),
      '#options' => $fuel_use_units_options,
      '#weight' => ++$weight,
    ];

    // Operator field.
    $operator_options = $this->getUserOptions(['farm_operator']);
    $form['application_record']['users'] = [
      '#type' => 'select',
      '#title' => $this->t('Operator'),
      '#options' => $operator_options,
      '#required' => TRUE,
      '#weight' => ++$weight,
    ];

    // Crop Photograph(s) - file - optional.
    // @todo Determine the final file upload location.
    $form['crop_photographs'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Crop Photograph(s)'),
      '#upload_location' => 'private://quick',
      '#upload_validators' => [
        'file_validate_extensions' => ['png gif jpg jpeg'],
      ],
      '#weight' => ++$weight,
    ];

    // Photographs of paper record(s) - file - optional.
    // @todo Determine the final file upload location.
    $form['photographs_of_paper_records'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Photographs of paper record(s)'),
      '#upload_location' => 'private://quick',
      '#upload_validators' => [
        'file_validate_extensions' => ['pdf png gif jpg jpeg'],
      ],
      '#weight' => ++$weight,
    ];

    // Equipment Settings - textarea - optional.
    $form['equipment_settings'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Equipment Settings'),
      '#weight' => ++$weight,
    ];

    // Notes - textarea - optional.
    $form['notes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Notes'),
      '#weight' => ++$weight,
    ];

    // Build status options.
    // @todo Load status options from log status options or workflow options.
    $status_options = [];

    // Job status - checkboxes - required.
    $form['job_status'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Job status'),
      '#options' => $status_options,
      '#required' => TRUE,
      '#weight' => ++$weight,
    ];

    unset($form['users']);
    unset($form['time']);

    return $form;
  }

}
