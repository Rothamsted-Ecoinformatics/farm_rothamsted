<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_comment\Hook;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\UserInterface;

/**
 * Entity hook implementations for farm_rothamsted_comment.
 */
class EntityHooks {

  public function __construct(
    protected readonly MailManagerInterface $mailManager,
    protected readonly AccountProxyInterface $currentUser,
  ) {
  }

  /**
   * Implements hook_entity_insert().
   */
  #[Hook('entity_insert')]
  public function entityInsert(EntityInterface $entity): void {
    if ($entity->getEntityTypeId() != 'comment') {
      return;
    }

    // Only send emails for comments about these entity types.
    $core_entity_type_ids = [
      'asset',
      'log',
      'plan',
    ];
    if (!in_array($entity->bundle(), $core_entity_type_ids)) {
      return;
    }

    // Add entity type logic.
    $emails = [];

    // Include commented entity owners.
    /** @var \Drupal\comment\CommentInterface $comment */
    $comment = $entity;
    if (($commented = $comment->getCommentedEntity()) && $commented->hasField('owner')) {
      $emails = array_map(function (UserInterface $user) {
        return $user->isBlocked() ? NULL : $user->getEmail();
      }, $commented->get('owner')->referencedEntities());
    }

    // Send comment update to authors of parent comments.
    $parent_count = 0;
    /** @var \Drupal\comment\Entity\Comment $comment */
    $comment = $entity;
    while ($comment->hasParentComment() && $parent_count < 5) {
      $parent_count++;
      $comment = $comment->getParentComment();
      if ($email = $comment->getAuthorEmail()) {
        $emails[] = $email;
      }
    }

    // Do not send updates to the current user.
    if ($current_user_email = $this->currentUser->getEmail()) {
      $emails = array_diff($emails, [$current_user_email]);
    }
    $emails = array_unique(array_filter($emails));

    // Bail if there is no one to send email.
    if (empty($emails)) {
      return;
    }

    // Get the entity and add a token variable for the entity type.
    $entity_type_id = $entity->getEntityTypeId();
    $params['entity_type_id'] = $entity_type_id;
    $params[$entity_type_id] = $entity;

    // Build email string.
    $emails = array_unique(array_filter($emails));
    $email_string = implode(', ', $emails);

    // Send mail.
    $this->mailManager->mail('farm_rothamsted_notification', 'comment', $email_string, 'en', $params);
  }

}
