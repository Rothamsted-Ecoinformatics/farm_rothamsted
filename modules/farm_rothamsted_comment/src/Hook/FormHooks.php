<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_comment\Hook;

use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\log\Entity\LogInterface;

/**
 * Form hook implementations for farm_rothamsted_comment.
 */
class FormHooks {

  /**
   * Implements hook_form_alter().
   */
  #[Hook('form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state, string $form_id): void {

    // Bail if not the right form.
    $form_object = $form_state->getFormObject();
    if ($form_id != 'comment_log_form' || !$form_object instanceof EntityFormInterface) {
      return;
    }

    // Default value.
    $existing_flags = array_column($form_object->getEntity()->getCommentedEntity()?->get('flag')?->getValue() ?? [], 'value');
    $default = in_array('review', $existing_flags);

    // Add checkbox to flag log for review.
    $form['log_flag_review'] = [
      '#type' => 'checkbox',
      '#title' => new TranslatableMarkup('Flag that the Log "Needs Review"'),
      '#default_value' => $default,
    ];
    $form['actions']['submit']['#submit'][] = [self::class, 'logFlagSubmit'];
  }

  /**
   * Submit callback for comment log flag form field.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function logFlagSubmit(array $form, FormStateInterface $form_state) {

    // Check that the log_flag_review field is set.
    if (!$form_state->hasValue('log_flag_review')) {
      return;
    }

    // Check if the box was checked to flag the log for review.
    $review_flag = $form_state->getValue('log_flag_review');

    // Check that we have a commented log.
    /** @var \Drupal\comment\CommentInterface $comment */
    $comment = $form_state->getFormObject()->getEntity();
    $log = $comment->getCommentedEntity();
    if (($log instanceof LogInterface) && $log->hasField('flag')) {

      // Add review to log flags.
      $flag_field = $log->get('flag');
      $existing_flags = array_column($flag_field->getValue(), 'value');
      $flag_field->setValue([]);

      // Append or remove the review flag.
      if ($review_flag) {
        $new_flags = array_unique(array_merge($existing_flags, ['review']));
        $message = '@user flagged log for review.';
      }
      else {
        $new_flags = array_diff($existing_flags, ['review']);
        $message = '@user unflagged log for review.';
      }
      foreach ($new_flags as $flag) {
        $flag_field->appendItem($flag);
      }

      // Return if there are no changes to the flag field.
      if ($new_flags === $existing_flags) {
        return;
      }

      // Set a revision and save the log.
      $log->setNewRevision(TRUE);
      $log->setRevisionUser($comment->getOwner());
      // phpcs:ignore
      $log->setRevisionLogMessage(new TranslatableMarkup($message, ['@user' => $comment->getOwner()->getDisplayName()]));
      $log->save();
    }
  }

}
