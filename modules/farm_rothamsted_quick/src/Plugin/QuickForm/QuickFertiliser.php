<?php

namespace Drupal\farm_rothamsted_quick\Plugin\QuickForm;

use Drupal\Core\Form\FormStateInterface;

/**
 * Fertiliser quick form.
 *
 * @QuickForm(
 *   id = "fertiliser",
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
  protected $parentLogCategoryName = 'Fertiliser categories';

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
  protected bool $productsTab = TRUE;

  /**
   * {@inheritdoc}
   */
  protected int $productsMinimum = 1;

  /**
   * {@inheritdoc}
   */
  protected $productBatchNum = TRUE;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Rename the products applied tab to be Fertiliser.
    $fertiliser = $form['products'];
    $fertiliser['#title'] = $this->t('Fertiliser');

    // Add to the operations tab.
    $operation = $form['operation'];

    // Health & safety tab.
    $health_and_safety = [
      '#type' => 'details',
      '#title' => $this->t('Health &amp; Safety'),
      '#group' => 'tabs',
      '#weight' => 6,
    ];

    // Spray application rate units.
    $application_rate_units_options = $this->getChildTermOptionsByName('unit', 'Volume per unit area');

    // Add inline wrapper for the fertiliser treated fields.
    $fertiliser['treated_wrapper'] = $this->buildInlineWrapper();

    // Treated area.
    $fertiliser['treated_wrapper']['machine_treated_area'] = $this->buildQuantityField([
      'title' => $this->t('Machine treated area'),
      'description' => $this->t('The total area to which the combined product(s) were applied, as recorded by the tractor or other equipment. If part of a hectare, please give the area to two decimal places.'),
      'measure' => ['#value' => 'area'],
      'units' => ['#value' => 'ha'],
      'required' => TRUE,
    ]);

    // Field treated area.
    $fertiliser['treated_wrapper']['field_treated_area'] = $this->buildQuantityField([
      'title' => $this->t('Field treated area'),
      'description' => $this->t('The total field area to which the combined product(s) were applied, to two decimal places. If part of a hectare, please give the area to two decimal places.'),
      'measure' => ['#value' => 'area'],
      'units' => ['#value' => 'ha'],
      'required' => TRUE,
    ]);

    // Total applied units.
    $total_applied_unit_options = $this->getChildTermOptionsByName('unit', 'Weight');

    // Total applied.
    $fertiliser['treated_wrapper']['total_applied'] = $this->buildQuantityField([
      'title' => $this->t('Total applied'),
      'description' => $this->t('The total amount of product required to cover the field area(s).'),
      'measure' => ['#value' => 'weight'],
      'units' => ['#options' => $total_applied_unit_options],
      'required' => TRUE,
    ]);

    // Target application rate.
    $fertiliser['treated_wrapper']['target_application_rate'] = $this->buildQuantityField([
      'title' => $this->t('Target application rate'),
      'description' => $this->t('The volume of product per unit area that needs to be applied in order to achieve the desired nutrient rate(s).'),
      'measure' => ['#value' => 'rate'],
      'units' => ['#options' => $application_rate_units_options],
    ]);

    // Move recommendation fields to fertiliser group.
    foreach (['recommendation_number', 'recommendation_files'] as $field_name) {
      $fertiliser[$field_name] = $form['setup'][$field_name];
      unset($form['setup'][$field_name]);
    }

    // Add the fertiliser tab back as the products tab.
    $form['products'] = $fertiliser;

    // COSSH Hazard Assessments.
    $health_and_safety['cossh_hazard'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('COSSH Hazard Assessments'),
      '#description' => $this->t('The COSHH assessments which need to be considered.'),
      '#options' => farm_rothamsted_cossh_hazard_options(),
      '#required' => TRUE,
    ];

    // Add the health and safety tab and fields to the form.
    $form['health_and_safety'] = $health_and_safety;

    // Add the operation tab and fields to the form.
    $form['operation'] = $operation;

    return $form;
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

  /**
   * {@inheritdoc}
   */
  protected function getLogName(array $form, FormStateInterface $form_state): string {

    // Get all of the submitted material_types.
    $material_type_names = [];
    if ($product_count = $form_state->get('product_count')) {
      for ($i = 0; $i < $product_count; $i++) {
        $material_id = $form_state->getValue(['products', $i, 'product_wrapper', 'product']);
        $material_type_names[] = $this->entityTypeManager->getStorage('taxonomy_term')->load($material_id)->label();
      }
    }

    // Generate the log name.
    $name_parts = [
      'prefix' => 'Nutrient Input: ',
      'products' => implode(', ', $material_type_names),
    ];
    $priority_keys = ['prefix', 'products'];
    return $this->prioritizedString($name_parts, $priority_keys);
  }

  /**
   * {@inheritdoc}
   */
  protected function getQuantities(array $field_keys, FormStateInterface $form_state): array {
    $field_keys[] = 'machine_treated_area';
    $field_keys[] = 'field_treated_area';
    $field_keys[] = 'total_applied';
    $field_keys[] = 'target_application_rate';
    return parent::getQuantities($field_keys, $form_state);
  }

}
