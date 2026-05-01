<?php

namespace Drupal\farm_rothamsted_quick\Plugin\QuickForm;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;

/**
 * Operations quick form.
 *
 * @QuickForm(
 *   id = "field_operations",
 *   label = @Translation("Field operations"),
 *   description = @Translation("Create operation records."),
 *   helpText = @Translation("Use this form to record operation records."),
 *   permissions = {
 *     "create activity log",
 *   }
 * )
 */
class QuickOperation extends QuickExperimentFormBase {

  /**
   * {@inheritdoc}
   */
  protected $logType = 'activity';

  /**
   * {@inheritdoc}
   */
  protected $parentLogCategoryName = 'Operation categories';

  /**
   * {@inheritdoc}
   */
  protected $tractorField = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $machineryEquipmentTypes = ['Cultivation Equipment'];

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Change log categories to use a dependent and dynamic form field.
    $parent_category_options = $this->getChildTermOptionsByName('log_category', $this->parentLogCategoryName, 1);
    $form['setup']['log_category'] = [];
    $form['setup']['log_category']['log_category_parent'] = [
      '#type' => 'select',
      '#title' => $this->t('Log category type'),
      '#options' => $parent_category_options,
      '#default_value' => '',
      '#empty_value' => '',
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [$this, 'logCategoryParentCallback'],
        'event' => 'change',
        'wrapper' => 'log_category_wrapper',
      ],
    ];

    // If the log_category_parent changed, get the new value to build the final
    // log_category options.
    $category_options = [];
    if (($trigger = $form_state->getTriggeringElement())
        && NestedArray::getValue($trigger['#array_parents'], [2]) == 'log_category_parent') {
      if ($parent_category_id = $trigger['#value']) {
        $category_options = $this->getTermTreeOptions('log_category', $parent_category_id);
      }
    }
    // Else get the previous product_type from form state.
    elseif ($parent_category_id = $form_state->get('log_category_parent')) {
      $category_options = $this->getTermTreeOptions('log_category', $parent_category_id);
    }
    // Always save the product_type to form state.
    $form_state->set('log_category_parent', $parent_category_id);

    // Finally, add log_category single select.
    $form['setup']['log_category']['log_category'] = [
      '#type' => 'select',
      '#title' => $this->t('Log category'),
      '#options' => $category_options,
      '#required' => TRUE,
      '#prefix' => "<div id='log_category_wrapper'>",
      '#suffix' => "</div>",
    ];

    // Add to the operation tab.
    $operation = &$form['operation'];

    // Task tab.
    $task = [
      '#type' => 'details',
      '#title' => $this->t('Task'),
      '#group' => 'tabs',
      '#weight' => 0,
    ];

    // Task info wrapper.
    $task['info'] = $this->buildInlineWrapper();

    // Depth worked.
    $depth_worked_units_options = [
      'cm' => 'cm',
      'in' => 'in',
    ];
    $task['info']['depth'] = $this->buildQuantityField([
      'title' => $this->t('Depth worked'),
      'description' => $this->t('Put "0" for surface cultivation (e.g. rolling) or leave blank if the operation does not relate to soil movement (e.g. mowing).'),
      'measure' => ['#value' => 'length'],
      'units' => ['#options' => $depth_worked_units_options],
    ]);

    // Working width.
    $task['info']['working_width'] = $this->buildQuantityField([
      'title' => $this->t('Working width'),
      'description' => $this->t('The working width of any machinery in meters, where applicable.'),
      'measure' => ['#value' => 'length'],
      'units' => ['#value' => 'm'],
    ]);

    // Define direction options.
    $direction_options = [
      '',
      'N',
      'NE',
      'E',
      'SE',
      'S',
      'SW',
      'W',
      'NW',
    ];

    // Direction of work (driven).
    $task['info']['direction'] = [
      '#type' => 'select',
      '#title' => $this->t('Direction of work driven'),
      '#description' => $this->t('The direction driven, where relevant.'),
      '#options' => array_combine($direction_options, $direction_options),
      '#weight' => 12,
    ];

    // Plough thrown (if applicable).
    $task['info']['thrown'] = [
      '#type' => 'select',
      '#title' => $this->t('Plough thrown (if applicable)'),
      '#options' => array_combine($direction_options, $direction_options),
      '#weight' => 13,
    ];

    // Water volume.
    $water_volume_units_options = [
      'l' => 'l',
      'gal' => 'gal',
    ];
    $task['water_volume'] = $this->buildQuantityField([
      'title' => $this->t('Water volume'),
      'description' => $this->t('The total amount of water used.'),
      'measure' => ['#value' => 'volume'],
      'units' => ['#options' => $water_volume_units_options],
    ]);

    // Water rate.
    $water_rate_units_options = [
      'mm' => 'mm',
    ];
    $task['water_rate'] = $this->buildQuantityField([
      'title' => $this->t('Water rate'),
      'description' => $this->t('Used for recording irrigation. The amount of water applied in mm as a rain gauge would record it. A water rate of 1mm = 10m3 water/ha. For older systems measuring in inches, 24mm is equivalent to an inch of rain (12mm for half an inch).'),
      'measure' => ['#value' => 'length'],
      'units' => ['#options' => $water_rate_units_options],
    ]);

    // Move recommendation fields to task group.
    foreach (['recommendation_number', 'recommendation_files'] as $field_name) {
      $task[$field_name] = $form['setup'][$field_name];
      unset($form['setup'][$field_name]);
    }

    // Add the operations tab and fields to the form.
    $form['task'] = $task;

    // Justification/Target.
    $operation['justification_target'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Justification/Target'),
      '#description' => $this->t('The reason the operation is necessary, and any target pest(s) where applicable.'),
      '#weight' => 15,
    ];

    return $form;
  }

  /**
   * Log category parent ajax callback.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The log category field.
   */
  public function logCategoryParentCallback(array &$form, FormStateInterface $form_state) {
    return $form['setup']['log_category']['log_category'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getLogName(array $form, FormStateInterface $form_state): string {

    // Load selected categories.
    $category_ids = $form_state->getValue('log_category');
    if (!is_array($category_ids)) {
      $category_ids = [$category_ids];
    }

    $terms = Term::loadMultiple($category_ids);
    $term_labels = array_map(function (TermInterface $term) {
      return $term->label();
    }, $terms);
    $term_string = implode(', ', $term_labels);
    return "Operation: $term_string";
  }

  /**
   * {@inheritdoc}
   */
  protected function getQuantities(array $field_keys, FormStateInterface $form_state): array {
    array_push(
      $field_keys,
      'depth',
      'working_width',
      'water_volume',
      'water_rate',
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
          'key' => 'direction',
          'label' => $this->t('Direction of work driven'),
        ],
        [
          'key' => 'thrown',
          'label' => $this->t('Plough thrown'),
        ],
        [
          'key' => 'justification_target',
          'label' => $this->t('Justification/Target'),
        ],
      ]
    );
    return parent::prepareNotes($note_fields, $form_state);
  }

}
