<?php

namespace Drupal\farm_rothamsted\Plugin\QuickForm;

use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Fertiliser quick form.
 *
 * @QuickForm(
 *   id = "farm_rothamsted_fertiliser_quick_form",
 *   label = @Translation("Fertiliser"),
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
  protected $equipmentGroupNames = ['Tractor Equipment', 'Fertiliser Equipment'];

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    /** @var \Drupal\taxonomy\TermStorage $term_storage */
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');

    // Crops form placeholder.
    $form['crops'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Crops'),
      '#placeholder' => $this->t('TBD'),
      '#required' => TRUE,
    ];

    // Tractor form placeholder.
    $form['tractor'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tractor'),
      '#placeholder' => $this->t('TBD'),
      '#required' => TRUE,
    ];

    // Machinery form placeholder.
    $form['machinery'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Machinery'),
      '#placeholder' => $this->t('TBD'),
      '#required' => TRUE,
    ];

    // Recommendation Number form placeholder.
    $form['recommendation_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Recommendation Number'),
      '#placeholder' => $this->t('TBD'),
      '#required' => TRUE,
    ];

    // Recommendation files form placeholder.
    $form['recommendation_files'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Recommendation files'),
      '#placeholder' => $this->t('TBD'),
      '#required' => TRUE,
    ];

    // Scheduled by form placeholder.
    $form['scheduled_by'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Scheduled by'),
      '#placeholder' => $this->t('TBD'),
      '#required' => TRUE,
    ];

    // Scheduled date and time form placeholder.
    $form['scheduled_date_and_time'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Scheduled date and time'),
      '#placeholder' => $this->t('TBD'),
      '#required' => TRUE,
    ];

    // Log name form placeholder.
    $form['log_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Log name'),
      '#placeholder' => $this->t('TBD'),
      '#required' => TRUE,
    ];

    // Assigned to form placeholder.
    $form['assigned_to'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Assigned to'),
      '#placeholder' => $this->t('TBD'),
      '#required' => TRUE,
    ];

    // Job status form placeholder.
    $form['job_status'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Job status'),
      '#placeholder' => $this->t('TBD'),
      '#required' => TRUE,
    ];

    // Nutrient Input form placeholder.
    $form['nutrient_input'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nutrient Input'),
      '#placeholder' => $this->t('TBD'),
      '#required' => TRUE,
    ];

    // Product Type form placeholder.
    $form['product_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Product Type'),
      '#placeholder' => $this->t('TBD'),
      '#required' => TRUE,
    ];

    // Product form placeholder.
    $form['product'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Product'),
      '#placeholder' => $this->t('TBD'),
      '#required' => TRUE,
    ];

    // Nutrient form placeholder.
    $form['nutrient'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nutrient'),
      '#placeholder' => $this->t('TBD'),
      '#required' => TRUE,
    ];

    // Nutrient content form placeholder.
    $form['nutrient_content'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nutrient content'),
      '#placeholder' => $this->t('TBD'),
      '#required' => TRUE,
    ];

    // Nutrient application rate form placeholder.
    $form['nutrient_application_rate'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nutrient application rate'),
      '#placeholder' => $this->t('TBD'),
      '#required' => TRUE,
    ];

    // Product application rate form placeholder.
    $form['product_application_rate'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Product application rate'),
      '#placeholder' => $this->t('TBD'),
      '#required' => TRUE,
    ];

    // Product volume form placeholder.
    $form['product_volume'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Product volume'),
      '#placeholder' => $this->t('TBD'),
      '#required' => TRUE,
    ];

    // COSSH Hazard Assessments form placeholder.
    $form['cossh_hazard_assessments'] = [
      '#type' => 'textfield',
      '#title' => $this->t('COSSH Hazard Assessments'),
      '#placeholder' => $this->t('TBD'),
      '#required' => TRUE,
    ];

    // Operation start time and date form placeholder.
    $form['operation_start_time_and_date'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Operation start time and date'),
      '#placeholder' => $this->t('TBD'),
      '#required' => TRUE,
    ];

    // Tractor hours (start) form placeholder.
    $form['tractor_hours_start'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tractor hours (start)'),
      '#placeholder' => $this->t('TBD'),
      '#required' => TRUE,
    ];

    // Tractor hours (end) form placeholder.
    $form['tractor_hours_end'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tractor hours (end)'),
      '#placeholder' => $this->t('TBD'),
      '#required' => TRUE,
    ];

    // Time taken form placeholder.
    $form['time_taken'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Time taken'),
      '#placeholder' => $this->t('TBD'),
      '#required' => TRUE,
    ];

    // Fuel use form placeholder.
    $form['fuel_use'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fuel use'),
      '#placeholder' => $this->t('TBD'),
      '#required' => TRUE,
    ];

    // Seed Label(s) form placeholder.
    $form['seed_labels'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Seed Label(s)'),
      '#placeholder' => $this->t('TBD'),
      '#required' => TRUE,
    ];

    // Crop Photograph(s) form placeholder.
    $form['crop_photographs'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Crop Photograph(s)'),
      '#placeholder' => $this->t('TBD'),
      '#required' => TRUE,
    ];

    // Photographs of paper record(s) form placeholder.
    $form['photographs_of_paper_records'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Photographs of paper record(s)'),
      '#placeholder' => $this->t('TBD'),
      '#required' => TRUE,
    ];

    // Notes form placeholder.
    $form['notes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Notes'),
      '#placeholder' => $this->t('TBD'),
      '#required' => TRUE,
    ];

    // Equipment Settings form placeholder.
    $form['equipment_settings'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Equipment Settings'),
      '#placeholder' => $this->t('TBD'),
      '#required' => TRUE,
    ];

    // Operator form placeholder.
    $form['operator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Operator'),
      '#placeholder' => $this->t('TBD'),
      '#required' => TRUE,
    ];

    // Load the top level Fertiliser material type.
    $fertiliser_terms = $term_storage->loadByProperties([
      'name' => 'Fertilisers',
      'vid' => 'material_type',
    ]);

    // Build fertiliser options. Default to none, showing message instead.
    $fertiliser_options = [$this->t('No Fertiliser material type configured.')];
    if ($fertiliser_term = reset($fertiliser_terms)) {
      reset($fertiliser_term);
      $fertiliser_children = $term_storage->loadChildren($fertiliser_term->id());
      $fertiliser_options = array_map(function (TermInterface $term) {
        return $term->label();
      }, $fertiliser_children);
    }

    // Fertiliser select list.
    $form['fertiliser'] = [
      '#type' => 'select',
      '#title' => $this->t('Fertiliser'),
      '#options' => $fertiliser_options,
      '#required' => TRUE,
    ];

    // Fertiliser rate.
    $form['rate'] = [
      '#type' => 'number',
      '#title' => $this->t('Rate of application (kg/ha)'),
      '#field_suffix' => $this->t('kg/ha'),
      '#required' => TRUE,
    ];

    return $form;
  }

}
