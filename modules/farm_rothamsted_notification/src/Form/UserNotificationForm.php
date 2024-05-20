<?php

namespace Drupal\farm_rothamsted_notification\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;

/**
 * Form for modifying user notifications.
 */
class UserNotificationForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'farm_rothamsted_notification_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL) {

    // Bail if no user.
    if (!$user) {
      return $form;
    }
    $form_state->set('user', $user);

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Email notifications'),
      '#default_value' => $user->get('rothamsted_notification_email')->value,
    ];

    $form['researcher'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Researcher updates'),
      '#description' => $this->t('Receive updates if someone makes a change to your Researcher profile. On by default, sent as soon as a change is made.'),
      '#default_value' => $user->get('rothamsted_notification_researcher')->value,
    ];

    $form['program'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Research Program updates'),
      '#description' => $this->t('Receive updates if someone makes changes to Research Program where you are named as a Principal Investigator. On by default, sent as soon as a change is made.'),
      '#default_value' => $user->get('rothamsted_notification_program')->value,
    ];

    $form['log'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log notifications'),
      '#default_value' => $user->get('rothamsted_notification_log')->value,
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\user\UserInterface $user */
    $user = $form_state->get('user');
    if ($user && $form_state->hasValue('enabled')) {
      $user->set('rothamsted_notification_email', $form_state->getValue('enabled', FALSE));
      $user->set('rothamsted_notification_researcher', $form_state->getValue('researcher', FALSE));
      $user->set('rothamsted_notification_program', $form_state->getValue('program', FALSE));
      $user->set('rothamsted_notification_log', $form_state->getValue('log', FALSE));
      $user->save();
      $this->messenger()->addStatus($this->t('Updated notification settings.'));
    }
  }

}
