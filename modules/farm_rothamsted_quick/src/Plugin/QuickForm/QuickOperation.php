<?php

namespace Drupal\farm_rothamsted_quick\Plugin\QuickForm;

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
  protected $machineryGroupNames = ['Cultivation Equipment'];

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

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
    $task['info']['depth'] = $this->buildQuantityField([
      'title' => $this->t('Depth worked (cm)'),
      'description' => $this->t('Put "0" for surface cultivation (e.g. rolling) or leave blank if the operation does not relate to soil movement (e.g. mowing).'),
      'measure' => ['#value' => 'length'],
      'units' => ['#value' => 'cm'],
    ]);

    // Working width.
    $task['info']['working_width'] = $this->buildQuantityField([
      'title' => $this->t('Working width (m)'),
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
    $field_keys[] = 'depth';
    $field_keys[] = 'working_width';
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
