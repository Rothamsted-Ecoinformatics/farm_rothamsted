<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted\Hook;

use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Form hook implementations for farm_rothamsted.
 */
class FormHooks {

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_user_form_alter')]
  public function formUserFormAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    // Disable the email field if the user does not have edit access.
    $form_object = $form_state->getFormObject();
    if (!$form_object instanceof EntityFormInterface) {
      return;
    }
    $user = $form_object->getEntity();
    $form['account']['mail']['#disabled'] = !$user->get('mail')->access('edit');
  }

}
