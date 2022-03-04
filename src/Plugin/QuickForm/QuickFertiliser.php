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
  protected bool $productsTab = TRUE;

  /**
   * {@inheritdoc}
   */
  protected bool $productBatchNum = TRUE;

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

    // Make the product labels required.
    // @todo Make this field required.
    // If a file is uploaded after the products are filled in then it breaks.
    $fertiliser['product_labels']['#required'] = FALSE;

    // Spray application rate units.
    $application_rate_units_options = $this->getChildTermOptionsByName('unit', 'Spray');

    // Target application rate.
    $fertiliser['target_application_rate'] = $this->buildQuantityField([
      'title' => $this->t('Target application rate'),
      'description' => $this->t('The volume of product per unit area that needs to be applied in order to achieve the desired nutrient rate(s).'),
      'measure' => ['#value' => 'rate'],
      'units' => ['#options' => $application_rate_units_options],
      'required' => TRUE,
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

    // Specify weight on the time wrappers so we can add fields below them.
    $operation['time']['#weight'] = -10;
    $operation['tractor_time']['#weight'] = -10;

    // Add inline wrapper for the fertiliser treated fields.
    $operation['treated_wrapper'] = $this->buildInlineWrapper();
    $operation['treated_wrapper']['#weight'] = -5;

    // Treated area.
    $operation['treated_wrapper']['treated_area'] = $this->buildQuantityField([
      'title' => $this->t('Treated area'),
      'description' => $this->t('The total area to which the combined product(s) were applied, as recorded by the tractor or other equipment.'),
      'measure' => ['#value' => 'area'],
      'units' => ['#value' => 'ha'],
      'required' => TRUE,
    ]);

    // Application volume units.
    $application_volume_units_options = $this->getChildTermOptionsByName('unit', 'Volume');

    // Total volume applied.
    $operation['treated_wrapper']['total_volume_applied'] = $this->buildQuantityField([
      'title' => $this->t('Total volume applied'),
      'description' => $this->t('The total amount of product required to cover the field area(s).'),
      'measure' => ['#value' => 'volume'],
      'units' => ['#options' => $application_volume_units_options],
      'required' => TRUE,
    ]);

    // Add the operation tab and fields to the form.
    $form['operation'] = $operation;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getQuantities(array $field_keys, FormStateInterface $form_state): array {
    $field_keys[] = 'target_application_rate';
    $field_keys[] = 'treated_area';
    $field_keys[] = 'total_volume_applied';
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
