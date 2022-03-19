<?php

namespace Drupal\farm_rothamsted_quick\Plugin\QuickForm;

use Drupal\Core\Form\FormStateInterface;

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

    // Task tab.
    $task = [
      '#type' => 'details',
      '#title' => $this->t('Task'),
      '#group' => 'tabs',
      '#weight' => 0,
    ];

    // Build associate arrays of task options.
    $grass_harvest_options = [
      $this->t('Mowing'),
      $this->t('Silage pick up'),
      $this->t('Bailing'),
      $this->t('Tedding/Spreading grass'),
      $this->t('Rowing up'),
      $this->t('Hay turning'),
    ];
    $grassland_options = [
      $this->t('Flat roll'),
      $this->t('Chain harrow'),
      $this->t('Aeration'),
      $this->t('Topping'),
    ];
    $cultivation_options = [
      $this->t('Plough'),
      $this->t('Power harrow'),
      $this->t('Rolling'),
      $this->t('Rotavate'),
      $this->t('Sub soil/ripping'),
      $this->t('Mole plough'),
      $this->t('Cultivate/level'),
      $this->t('Hoeing'),
      $this->t('Hand weeding'),
    ];
    $other_options = [
      $this->t('Hedge trimming'),
      $this->t('Drain trim'),
      $this->t('Drain burn'),
      $this->t('Irrigation'),
      $this->t('Treatment'),
    ];

    // Combine all options into option groups.
    $operation_task_options = [
      'Grass harvest' => array_combine($grass_harvest_options, $grass_harvest_options),
      'Grassland maintenance' => array_combine($grassland_options, $grassland_options),
      'Cultivations' => array_combine($cultivation_options, $cultivation_options),
      'Other' => array_combine($other_options, $other_options),
    ];

    // Add a select element for the operation task.
    $task['operation_task'] = [
      '#type' => 'select',
      '#title' => $this->t('Task'),
      '#options' => $operation_task_options,
      '#required' => TRUE,
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

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getLogName(array $form, FormStateInterface $form_state): string {
    $task = $form_state->getValue('operation_task');
    return "Operation: $task";
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
          'key' => 'operation_task',
          'label' => $this->t('Task'),
        ],
        [
          'key' => 'direction',
          'label' => $this->t('Direction of work driven'),
        ],
        [
          'key' => 'thrown',
          'label' => $this->t('Plough thrown'),
        ],
      ]
    );
    return parent::prepareNotes($note_fields, $form_state);
  }

}
