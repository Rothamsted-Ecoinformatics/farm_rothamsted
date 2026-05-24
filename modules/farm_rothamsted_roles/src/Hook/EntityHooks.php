<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_roles\Hook;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\UserInterface;

/**
 * Entity hook implementations for farm_rothamsted_roles.
 */
class EntityHooks {

  use StringTranslationTrait;

  /**
   * Constructs an EntityHooks object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   */
  public function __construct(
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * Implements hook_entity_extra_field_info().
   */
  #[Hook('entity_extra_field_info')]
  public function entityExtraFieldInfo(): array {
    $fields['user']['user']['display']['rothamsted_user_roles'] = [
      'label' => $this->t('User roles'),
      'description' => $this->t("The user's roles and permissions."),
      'weight' => 0,
    ];
    return $fields;
  }

  /**
   * Implements hook_ENTITY_TYPE_view() for user entities.
   */
  #[Hook('user_view')]
  public function userView(array &$build, UserInterface $account, EntityViewDisplayInterface $display): void {
    $access = $account->id() === $this->currentUser->id() || $this->currentUser->hasPermission('administer users');
    if ($access && $display->getComponent('rothamsted_user_roles')) {
      $role_labels = array_map(function ($role) {
        return $role->label();
      }, $account->get('roles')->referencedEntities());
      $build['rothamsted_user_roles'] = [
        '#type' => 'item',
        '#markup' => '<h4 class="label">' . $this->t('User roles') . '</h4> ' . implode(', ', $role_labels),
      ];
    }
  }

}
