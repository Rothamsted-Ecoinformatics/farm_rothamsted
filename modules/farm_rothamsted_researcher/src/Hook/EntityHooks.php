<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_researcher\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\farm_rothamsted_researcher\Entity\RothamstedResearcherInterface;
use Drupal\user\UserInterface;

/**
 * Entity hook implementations for farm_rothamsted_researcher.
 */
class EntityHooks {

  use StringTranslationTrait;

  public function __construct(
    protected AccountProxyInterface $currentUser,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Implements hook_entity_form_display_alter().
   */
  #[Hook('entity_form_display_alter')]
  public function entityFormDisplayAlter(EntityFormDisplayInterface $form_display, array $context): void {
    $comment_bundles = [
      'rothamsted_researcher',
    ];
    if ($form_display->getTargetEntityTypeId() == 'comment' && in_array($context['bundle'], $comment_bundles) && $form_display->getMode() == 'default' && $form_display->isNew()) {
      $form_display->setComponent('author', [
        'region' => 'content',
        'settings' => [],
        'weight' => 0,
      ]);
      $form_display->setComponent('comment_body', [
        'type' => 'text_textarea',
        'region' => 'content',
        'settings' => [
          'rows' => 5,
          'placeholder' => '',
        ],
        'weight' => 1,
      ]);
      $form_display->removeComponent('subject');
    }
  }

  /**
   * Implements hook_entity_view_display_alter().
   */
  #[Hook('entity_view_display_alter')]
  public function entityViewDisplayAlter(EntityViewDisplayInterface $display, array $context): void {
    $comment_bundles = [
      'rothamsted_researcher',
    ];
    $display_modes = ['default', 'full'];
    if ($context['entity_type'] == 'comment' && in_array($context['bundle'], $comment_bundles) && in_array($display->getMode(), $display_modes) && $display->isNew()) {
      $display->setComponent('comment_body', [
        'type' => 'text_default',
        'label' => 'hidden',
        'region' => 'content',
        'settings' => [],
        'weight' => 0,
      ]);
      $display->setComponent('links', [
        'region' => 'content',
        'settings' => [],
        'weight' => 1,
      ]);
    }
  }

  /**
   * Implements hook_entity_extra_field_info().
   */
  #[Hook('entity_extra_field_info')]
  public function entityExtraFieldInfo(): array {
    $fields['user']['user']['display']['researcher_profile'] = [
      'label' => $this->t('Researcher profile roles'),
      'description' => $this->t("The user's researcher profile."),
      'weight' => -5,
    ];
    $fields['rothamsted_researcher']['rothamsted_researcher']['display']['user_profile'] = [
      'label' => $this->t('User profile'),
      'description' => $this->t('The researcher user profile.'),
      'weight' => 0,
    ];
    return $fields;
  }

  /**
   * Implements hook_ENTITY_TYPE_view() for user entities.
   */
  #[Hook('user_view')]
  public function userView(array &$build, UserInterface $account, EntityViewDisplayInterface $display): void {

    // Query for the current user's researcher profile.
    if ($display->getComponent('researcher_profile')) {
      $researchers = $this->entityTypeManager->getStorage('rothamsted_researcher')->loadByProperties([
        'farm_user' => $account->id(),
      ]);
      /** @var \Drupal\farm_rothamsted_researcher\Entity\RothamstedResearcherInterface $researcher */
      if ($researcher = reset($researchers)) {
        $build['researcher_profile'] = [
          '#type' => 'item',
          '#markup' => '<h4 class="label">' . $this->t('Researcher profile') . '</h4> ' . $researcher->toLink($researcher->label())->toString(),
        ];
      }
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_view() for rothamsted_researcher entities.
   */
  #[Hook('rothamsted_researcher_view')]
  public function rothamstedResearcherView(array &$build, RothamstedResearcherInterface $researcher, EntityViewDisplayInterface $display): void {
    if ($display->getComponent('user_profile')) {

      // Bail if researcher doesn't have a farm user.
      if ($researcher->get('farm_user')->isEmpty()) {
        return;
      }

      // Get referenced user.
      $users = $researcher->get('farm_user')->referencedEntities();
      $user = reset($users);

      // Include user roles if user has access.
      $role_labels = NULL;
      if ($user->id() === $this->currentUser->id() || $this->currentUser->hasPermission('administer users')) {
        $role_labels = implode(
          ', ',
          array_map(function ($role) {
            return $role->label();
          }, $user->get('roles')->referencedEntities())
        );
        $role_labels = " ($role_labels)";
      }

      // Build text.
      $build['researcher_profile'] = [
        '#type' => 'item',
        '#markup' => '<h4 class="label">' . $this->t('farmOS User profile') . '</h4> ' . $user->toLink($user->label())->toString() . $role_labels,
      ];
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_access().
   */
  #[Hook('rothamsted_researcher_access')]
  public function rothamstedResearcherAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    // Only check view and update operations.
    if (!in_array($operation, ['update', 'view']) || $entity->get('farm_user')->isEmpty()) {
      return AccessResult::neutral();
    }

    // Only check if the farm_user is the current user.
    if ($entity->farm_user->entity->id() == $account->id()) {
      // Allow access if the user has the view/update own permission.
      return AccessResultAllowed::allowedIf($account->hasPermission("$operation assigned rothamsted_researcher"));
    }

    // Else return neutral.
    return AccessResult::neutral();
  }

}
