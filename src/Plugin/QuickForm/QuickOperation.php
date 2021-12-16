<?php

namespace Drupal\farm_rothamsted\Plugin\QuickForm;

use Drupal\Core\Form\FormStateInterface;

/**
 * Operations quick form.
 *
 * @todo This was previously the cultivation quick form so we maintain that ID.
 *
 * @see https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/pull/6#issuecomment-903958799
 *
 * @QuickForm(
 *   id = "farm_rothamsted_cultivation_quick_form",
 *   label = @Translation("Operations"),
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
    $form['operation_task'] = [
      '#type' => 'select',
      '#title' => $this->t('Task'),
      '#options' => $operation_task_options,
      '#required' => TRUE,
    ];

    // Depth worked.
    $form['depth'] = [
      '#type' => 'number',
      '#title' => $this->t('Depth worked (centimeters)'),
      '#description' => $this->t('Put "0" for surface cultivation (e.g. rolling) or leave blank if the operation does not relate to soil movement (e.g. mowing).'),
      '#field_suffix' => $this->t('centimeters'),
      '#weight' => 11,
    ];

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
    $form['direction'] = [
      '#type' => 'select',
      '#title' => $this->t('Direction of work (driven)'),
      '#options' => array_combine($direction_options, $direction_options),
      '#weight' => 12,
    ];

    // Plough thrown (if applicable).
    $form['thrown'] = [
      '#type' => 'select',
      '#title' => $this->t('Plough thrown (if applicable)'),
      '#options' => array_combine($direction_options, $direction_options),
      '#weight' => 13,
    ];

    return $form;
  }

}
