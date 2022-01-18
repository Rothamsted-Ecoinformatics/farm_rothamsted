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
  protected $logType = 'input';

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
      '#description' => $this->t('Details about the type and quantity of starter fertilsier used.'),
      '#open' => FALSE,
      '#weight' => ++$weight,
    ];

    // Build product_type options.
    $product_type_options = $this->getTermOptions('material_type');

    // Product type - select - optional.
    $form['nutrient_input']['product_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Product type'),
      '#description' => $this->t('A list of different types of nutrient input (manure, compost, fertiliser, etc). The list can be expanded or amended in the inputs taxonomy.'),
      '#options' => $product_type_options,
      '#required' => TRUE,
      '#weight' => ++$weight,
    ];

    // Product - select - optional.
    $form['nutrient_input']['product'] = [
      '#type' => 'select',
      '#title' => $this->t('Product'),
      '#description' => $this->t('The product used.'),
      '#options' => $product_type_options,
      '#required' => TRUE,
      '#weight' => ++$weight,
    ];

    // Nutrient form placeholder.
    $form['nutrient_input']['nutrient'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nutrient'),
      '#description' => $this->t('The nutrients contained in the product.'),
      '#placeholder' => $this->t('TBD'),
      '#required' => FALSE,
      '#weight' => ++$weight,
    ];

    // Nutrient content - text - optional.
    $form['nutrient_input']['nutrient_content'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nutrient content (%)'),
      '#description' => $this->t('The proportion of the mineral in the product.'),
      '#weight' => ++$weight,
    ];

    // Nutrient application rate - number - required.
    $form['nutrient_input']['nutrient_application_rate'] = [
      '#type' => 'number',
      '#title' => $this->t('Nutrient application rate'),
      '#description' => $this->t('The volume of mineral per unit area that needs to be applied. This is an agronomic decision based on factors such as the crop, the field history and the location.'),
      '#required' => FALSE,
      '#weight' => ++$weight,
    ];

    // Build application rate units options from units / spray taxonomy.
    $application_rate_units_options = $this->getChildTermOptions('unit', 'spray');

    // Nutrient application rate - select - required.
    $form['nutrient_input']['nutrient_application_rate_units'] = [
      '#type' => 'select',
      '#title' => $this->t('Nutrient application rate units'),
      '#options' => $application_rate_units_options,
      '#required' => FALSE,
      '#weight' => ++$weight,
    ];

    // Product application rate - number - required.
    $form['nutrient_input']['product_application_rate'] = [
      '#type' => 'number',
      '#title' => $this->t('Product application rate'),
      '#description' => $this->t('The volume of product per unit area that needs to be applied in order to achieve the desired nutrient rate(s).'),
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

    // Product area - number - required.
    $form['nutrient_input']['product_area'] = [
      '#type' => 'number',
      '#title' => $this->t('Product area'),
      '#description' => $this->t('The total area that the product is being applied to. For example the area of the field, or the combined area of all the plots.'),
      '#required' => TRUE,
      '#weight' => ++$weight,
    ];

    // Product volume - number - required.
    $form['nutrient_input']['product_volume'] = [
      '#type' => 'number',
      '#title' => $this->t('Product volume'),
      '#description' => $this->t('The total amount of product required to cover the field area(s).'),
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

    // The following fields come after the recommendation fields.
    $weight = 15;

    // Build hazard options.
    // @todo Determine way to define hazard options. See issue #64.
    $hazard_options = [];

    // COSSH Hazard Assessments - checkboxes - required.
    $form['cossh_hazard_assessments'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('COSSH Hazard Assessments'),
      '#description' => $this->t('The COSHH assessments which need to be considered when handling fertilisers. Select all that apply.'),
      '#options' => $hazard_options,
      '#required' => TRUE,
      '#weight' => ++$weight,
    ];

    return $form;
  }

}
