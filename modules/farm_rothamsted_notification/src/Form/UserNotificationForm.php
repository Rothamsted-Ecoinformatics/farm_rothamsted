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
      '#description' => $this->t('Switch off all e-mail notifications. Please note that there are some e-mail notifications which cannot be switched off for authentic purposes. For example if someone creates a Research Profile on your behalf, or if someone names you on a Research Program, Proposal, Experiment or Design.'),
      '#default_value' => $user->get('rothamsted_notification_email')->value,
    ];

    $form['researcher'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Researcher Profile notifications'),
      '#description' => $this->t('Switch on/off e-mail notifications relating to changes to your Researcher profile in FarmOS. If this is switched off, you will no longer receive notifications if someone other than you edits your Researcher profile (e.g. an administrator). This is on by default. If you leave it on, you will receive e-mails as soon as any changes are made.'),
      '#default_value' => $user->get('rothamsted_notification_researcher')->value,
      '#states' => [
        'disabled' => [
          ':input[name="enabled"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['program'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Research Program notifications'),
      '#description' => $this->t('Switch on/off e-mail notifications relating to changes to any Research Programs you are associated with in FarmOS. If this is switched off, you will no longer receive notifications if someone other than you edits a Research Program where you are named as a PI (e.g. an administrator). This is on by default. If you leave it on, you will receive e-mails as soon as any changes are made.'),
      '#default_value' => $user->get('rothamsted_notification_program')->value,
      '#states' => [
        'disabled' => [
          ':input[name="enabled"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['log'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log notifications'),
      '#description' => $this->t('Switch on/off e-mail notifications for logs. If this is switched of you will no longer receive notifications when someone (e.g. farm staff) adds or edits the logs associated with the experiments you are named on. This is on by default. If you leave it on, you will receive e-mails as soon as new logs are added or any changes are made.'),
      '#default_value' => $user->get('rothamsted_notification_log')->value,
      '#states' => [
        'disabled' => [
          ':input[name="enabled"]' => ['checked' => FALSE],
        ],
      ],
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
