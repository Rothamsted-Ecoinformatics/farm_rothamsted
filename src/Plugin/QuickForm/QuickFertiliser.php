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
    $form = parent::buildForm($form, $form_state);

    // Fertiliser tab.
    $fertiliser = [
      '#type' => 'details',
      '#title' => $this->t('Fertiliser'),
      '#group' => 'tabs',
      '#weight' => 0,
    ];

    // Health & safety tab.
    $health_and_safety = [
      '#type' => 'details',
      '#title' => $this->t('Health &amp; Safety'),
      '#group' => 'tabs',
      '#weight' => 0,
    ];

    // Nutrient input.
    // @todo We need AJAX to populate multiple of these.
    $fertiliser['nutrient_input']['nutrient_count'] = [
      '#type' => 'select',
      '#title' => $this->t('How many nutrients are required?'),
      '#options' => array_combine(range(1, 5), range(1, 5)),
      '#default_value' => 1,
      '#ajax' => [
        'callback' => [$this, 'nutrientCallback'],
        'even' => 'change',
        'wrapper' => 'farm-rothamsted-nutrients',
      ],
    ];

    $fertiliser['nutrient_input']['nutrients'] = [
      '#prefix' => '<div id="farm-rothamsted-nutrients">',
      '#suffix' => '</div>',
    ];

    // Add fields for each nutrient.
    $fertiliser['nutrient_input']['nutrients']['#tree'] = TRUE;
    $quantities = $form_state->getValue('nutrient_count', 1);
    for ($i = 0; $i < $quantities; $i++) {

      // Fieldset for each nutrient.
      $fertiliser['nutrient_input']['nutrients'][$i] = [
        '#type' => 'details',
        '#title' => $this->t('Nutrient @number', ['@number' => $i + 1]),
        '#description' => $this->t('Details about the type and quantity of starter fertilsier used.'),
        '#collapsible' => TRUE,
        '#open' => TRUE,
      ];

      // Product wrapper.
      $product_wrapper = $this->buildInlineWrapper();

      // Build product_type options.
      $product_type_options = $this->getTermTreeOptions('material_type');

      // Product type - select - optional.
      $product_wrapper['product_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Product type'),
        '#description' => $this->t('A list of different types of nutrient input (manure, compost, fertiliser, etc). The list can be expanded or amended in the inputs taxonomy.'),
        '#options' => $product_type_options,
        '#required' => TRUE,
      ];

      // Product - select - optional.
      $product_wrapper['product'] = [
        '#type' => 'select',
        '#title' => $this->t('Product'),
        '#description' => $this->t('The product used.'),
        '#options' => $product_type_options,
        '#required' => TRUE,
      ];

      $fertiliser['nutrient_input']['nutrients'][$i]['product_wrapper'] = $product_wrapper;

      // Nutrient wrapper.
      $nutrient_wrapper = $this->buildInlineWrapper();

      // Nutrient form placeholder.
      $nutrient_wrapper['nutrient'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Nutrient'),
        '#description' => $this->t('The nutrients contained in the product.'),
        '#placeholder' => $this->t('TBD'),
        '#required' => FALSE,
        '#size' => 20,
      ];

      // Nutrient content - text - optional.
      $nutrient_wrapper['nutrient_content'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Nutrient content (%)'),
        '#description' => $this->t('The proportion of the mineral in the product.'),
        '#size' => 20,
      ];

      $fertiliser['nutrient_input']['nutrients'][$i]['nutrient_wrapper'] = $nutrient_wrapper;

      // Build application rate units options from units / spray taxonomy.
      $application_rate_units_options = $this->getChildTermOptionsByName('unit', 'spray');

      // Nutrient application rate - number.
      $fertiliser['nutrient_input']['nutrients'][$i]['nutrient_application_rate'] = $this->buildQuantityUnitsElement([
        '#type' => 'number',
        '#title' => $this->t('Nutrient application rate'),
        '#description' => $this->t('The volume of mineral per unit area that needs to be applied. This is an agronomic decision based on factors such as the crop, the field history and the location.'),
        '#required' => FALSE,
        '#units_type' => 'select',
        '#units_options' => $application_rate_units_options,
      ], 'nutrient_application_rate');

      // Product application rate - number - required.
      $fertiliser['nutrient_input']['nutrients'][$i]['product_application_rate'] = $this->buildQuantityUnitsElement([
        '#type' => 'number',
        '#title' => $this->t('Product application rate'),
        '#description' => $this->t('The volume of product per unit area that needs to be applied in order to achieve the desired nutrient rate(s).'),
        '#required' => TRUE,
        '#units_type' => 'select',
        '#units_options' => $application_rate_units_options,
      ], 'product_application_rate');

      // Product area - number - required.
      $fertiliser['nutrient_input']['nutrients'][$i]['product_area'] = [
        '#type' => 'number',
        '#title' => $this->t('Product area'),
        '#description' => $this->t('The total area that the product is being applied to. For example the area of the field, or the combined area of all the plots.'),
        '#required' => TRUE,
      ];

      // Build volume units options from units / volume taxonomy.
      // @todo We need to specify the correct fuel units.
      // The volume units are not the same for every field.
      $application_volume_units_options = [];

      // Product volume - number - required.
      $fertiliser['nutrient_input']['nutrients'][$i]['product_volume'] = $this->buildQuantityUnitsElement([
        '#type' => 'number',
        '#title' => $this->t('Product volume'),
        '#description' => $this->t('The total amount of product required to cover the field area(s).'),
        '#required' => TRUE,
        '#units_type' => 'select',
        '#units_options' => $application_volume_units_options,
      ], 'product_volume');

    }

    // COSSH Hazard Assessments.
    $health_and_safety['cossh_hazard'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('COSSH Hazard Assessments'),
      '#description' => $this->t('The COSHH assessments which need to be considered.'),
      '#options' => farm_rothamsted_cossh_hazard_options(),
      '#required' => TRUE,
    ];

    // Add the fertiliser tab and fields to the form.
    $form['fertiliser'] = $fertiliser;

    // Add the health and safety tab and fields to the form.
    $form['health_and_safety'] = $health_and_safety;

    return $form;
  }

  /**
   * Form ajax function for fertiliser quick form nutrients.
   */
  public function nutrientCallback(array $form, FormStateInterface $form_state) {
    return $form['fertiliser']['nutrient_input']['nutrients'];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareLog(array $form, FormStateInterface $form_state): array {
    $log = parent::prepareLog($form, $form_state);

    // COSSH Hazard Assessments.
    $log['cossh_hazard'] = array_values(array_filter($form_state->getValue('cossh_hazard')));

    return $log;
  }

}
