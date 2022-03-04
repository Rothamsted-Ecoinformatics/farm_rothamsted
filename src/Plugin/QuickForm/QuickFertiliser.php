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

      // Nutrient content.
      $nutrient_wrapper['nutrient_content'] = $this->buildQuantityField([
        'title' => $this->t('Nutrient content (%)'),
        'description' => $this->t('The proportion of the mineral in the product.'),
        'measure' => ['#value' => 'ratio'],
        'units' => ['#value' => '%'],
      ]);

      $fertiliser['nutrient_input']['nutrients'][$i]['nutrient_wrapper'] = $nutrient_wrapper;

      // Spray application rate units.
      $application_rate_units_options = $this->getChildTermOptionsByName('unit', 'spray');

      // Nutrient application rate.
      $nutrient_application_rate = [
        'title' => $this->t('Nutrient application rate'),
        'description' => $this->t('The volume of mineral per unit area that needs to be applied. This is an agronomic decision based on factors such as the crop, the field history and the location.'),
        'measure' => ['#value' => 'rate'],
        'units' => ['#options' => $application_rate_units_options],
      ];
      $fertiliser['nutrient_input']['nutrients'][$i]['nutrient_application_rate'] = $this->buildQuantityField($nutrient_application_rate);

      // Product application rate.
      $product_application_rate = [
        'title' => $this->t('Product application rate'),
        'description' => $this->t('The volume of product per unit area that needs to be applied in order to achieve the desired nutrient rate(s).'),
        'measure' => ['#value' => 'rate'],
        'units' => ['#options' => $application_rate_units_options],
        'required' => TRUE,
      ];
      $fertiliser['nutrient_input']['nutrients'][$i]['product_application_rate'] = $this->buildQuantityField($product_application_rate);

      // Product area.
      $fertiliser['nutrient_input']['nutrients'][$i]['product_area'] = $this->buildQuantityField([
        'title' => $this->t('Product area'),
        'description' => $this->t('The total area that the product is being applied to. For example the area of the field, or the combined area of all the plots.'),
        'measure' => ['#value' => 'area'],
        'units' => ['#value' => 'ha'],
        'required' => TRUE,
      ]);

      // Application volume units.
      $application_volume_units_options = $this->getChildTermOptionsByName('unit', 'Volume');

      // Product volume.
      $product_volume = [
        'title' => $this->t('Product volume'),
        'description' => $this->t('The total amount of product required to cover the field area(s).'),
        'measure' => ['#value' => 'volume'],
        'units' => ['#options' => $application_volume_units_options],
        'required' => TRUE,
      ];
      $fertiliser['nutrient_input']['nutrients'][$i]['product_volume'] = $this->buildQuantityField($product_volume);
    }

    // Move recommendation fields to fertiliser group.
    foreach (['recommendation_number', 'recommendation_files'] as $field_name) {
      $fertiliser[$field_name] = $form['setup'][$field_name];
      unset($form['setup'][$field_name]);
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
  protected function getQuantities(array $field_keys, FormStateInterface $form_state): array {
    // @todo Include nutrient quantities with correct labels.
    $nutrient_count = $form_state->getValue('nutrient_count');
    for ($i = 0; $i < $nutrient_count; $i++) {
      $field_keys[] = ['nutrients', $i, 'nutrient_wrapper', 'nutrient_content'];
      $field_keys[] = ['nutrients', $i, 'nutrient_application_rate'];
      $field_keys[] = ['nutrients', $i, 'product_area'];
      $field_keys[] = ['nutrients', $i, 'product_volume'];
    }
    return parent::getQuantities($field_keys, $form_state);
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
