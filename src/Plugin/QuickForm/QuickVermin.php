<?php

namespace Drupal\farm_rothamsted\Plugin\QuickForm;

use Drupal\Core\Form\FormStateInterface;

/**
 * Vermin quick form.
 *
 * @QuickForm(
 *   id = "farm_rothamsted_vermin_quick_form",
 *   label = @Translation("Vermin control"),
 *   description = @Translation("Create Vermin control records."),
 *   helpText = @Translation("Use this form to record vermin action records."),
 *   permissions = {
 *     "create input log",
 *   }
 * )
 */
class QuickVermin extends QuickExperimentFormBase {

  /**
   * {@inheritdoc}
   */
  protected $logType = 'input';

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Vermin tab.
    $vermin = [
      '#type' => 'details',
      '#title' => $this->t('Vermin control'),
      '#group' => 'tabs',
      '#weight' => 0,
    ];

    // Action count.
    $vermin['control_actions']['action_count'] = [
      '#type' => 'select',
      '#title' => $this->t('How many control actions were there?'),
      '#options' => array_combine(range(1, 25), range(1, 25)),
      '#default_value' => 1,
      '#ajax' => [
        'callback' => [$this, 'actionsCallback'],
        'event' => 'change',
        'wrapper' => 'farm-rothamsted-vermin-control-actions',
      ],
    ];

    // Create a wrapper around all action fields, for AJAX replacement.
    $vermin['control_actions']['actions'] = [
      '#prefix' => '<div id="farm-rothamsted-vermin-control-actions">',
      '#suffix' => '</div>',
    ];

    // Add fields for each action.
    $vermin['control_actions']['actions']['#tree'] = TRUE;
    $quantities = $form_state->getValue('action_count', 1);
    for ($i = 0; $i < $quantities; $i++) {

      // Fieldset for each action.
      $vermin['control_actions']['actions'][$i] = [
        '#type' => 'details',
        '#title' => $this->t('Control action @number', ['@number' => $i + 1]),
        '#collapsible' => TRUE,
        '#open' => TRUE,
      ];

      // Date checked.
      $vermin['control_actions']['actions'][$i]['date_checked'] = [
        '#type' => 'date',
        '#title' => $this->t('Date checked'),
        '#description' => $this->t('The date that the control has been checked.'),
        '#required' => TRUE,
      ];

      // Risk assessment.
      $vermin['control_actions']['actions'][$i]['risk_assessment'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Risk assessment'),
        '#description' => $this->t('Confirm a risk assessment has been made.'),
        '#required' => TRUE,
      ];

      // COSHH completed.
      $vermin['control_actions']['actions'][$i]['coshh_completed'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('COSHH completed'),
        '#description' => $this->t('Confirm COSHH is completed.'),
        '#required' => TRUE,
      ];

      // Bait type.
      $vermin['control_actions']['actions'][$i]['bait_type'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Bait type'),
        '#description' => $this->t('Bait type used.'),
        '#required' => TRUE,
      ];

      // Location / Station ID Number.
      $vermin['control_actions']['actions'][$i]['location_number'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Location / station ID number All'),
        '#description' => $this->t('All locations.'),
        '#required' => TRUE,
      ];

      // Findings observations.
      $vermin['control_actions']['actions'][$i]['findings'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Findings observations'),
        '#description' => $this->t('Describe findings and observations.'),
        '#required' => TRUE,
      ];

      // Action required.
      $vermin['control_actions']['actions'][$i]['action_required'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Action required'),
        '#description' => $this->t('Is further action required.'),
      ];

      // Date completed.
      $vermin['control_actions']['actions'][$i]['date_completed'] = [
        '#type' => 'date',
        '#title' => $this->t('Date completed'),
        '#description' => $this->t('The date that the control is completed.'),
        '#required' => TRUE,
      ];
    }

    // Add the vermin tab and fields to the form.
    $form['vermin'] = $vermin;

    return $form;
  }

  /**
   * Form ajax function for product quick form actions.
   */
  public function actionsCallback(array $form, FormStateInterface $form_state) {
    return $form['vermin']['control_actions']['actions'];
  }

}
