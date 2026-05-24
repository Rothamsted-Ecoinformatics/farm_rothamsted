<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_notification\Hook;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;

/**
 * Mail hook implementations for farm_rothamsted_notification.
 */
class MailHooks {

  /**
   * Constructs a MailHooks object.
   *
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected Token $token,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Implements hook_mail().
   */
  #[Hook('mail')]
  public function mail(string $key, array &$message, array $params): void {

    // Get entity type information.
    $entity_type_id = NULL;
    $entity_type_label = NULL;
    if (isset($params['entity_type_id']) && $entity_type_id = $params['entity_type_id']) {
      $entity_type_label = $this->entityTypeManager->getDefinition($entity_type_id)->getLabel();
    }

    // Build the message.
    $variables = $params;
    $subject_template = NULL;
    $body_templates = NULL;
    switch ($key) {

      case 'comment':
        $subject_template = "[site:name]: [$entity_type_id:author:display-name] commented on [$entity_type_id:entity:name]";
        $body_templates[] = "[$entity_type_id:author:display-name] commented on [$entity_type_id:entity:name]: [$entity_type_id:body]";
        $body_templates[] = "View and respond to the $entity_type_label here: [$entity_type_id:url:absolute]";
        break;

      case 'entity_template':

        // Get the entity and add a token variable for the entity type.
        $entity = $params['entity'];
        $entity_type_id = $entity->getEntityTypeId();
        $variables[$entity_type_id] = $entity;

        // Set subject and body template.
        $subject_template = $params['subject_template'];
        $body_templates = $params['body_template'];
        break;

      // Do not send the message.
      default:
        $message['send'] = FALSE;
    }

    $body_templates[] = '-- [site:name] team';

    // Replace tokens in the subject and body.
    $subject = $this->token->replace($subject_template, $variables);
    $body = array_map(function ($line) use ($variables) {
      return $this->token->replace($line, $variables);
    }, $body_templates);

    // Add configure-notifications link.
    $url = Url::fromRoute('farm_rothamsted_notification.user_notification_redirect')->setAbsolute()->toString();
    $body = str_replace('[configure-notifications]', $url, $body);

    $message['subject'] = $subject;
    $message['body'] = $body;
  }

}
