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
      $user->save();
      $this->messenger()->addStatus($this->t('Updated notification settings.'));
    }
  }

}
