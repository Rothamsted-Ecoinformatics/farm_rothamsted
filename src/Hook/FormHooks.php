<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted\Hook;

use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\asset\Entity\AssetInterface;
use Drupal\farm_location\AssetLocationInterface;
use Drupal\farm_quick\QuickFormInstanceManagerInterface;
use Drupal\system\Entity\Action;

/**
 * Form hook implementations for farm_rothamsted.
 */
class FormHooks {

  public function __construct(
    protected readonly AssetLocationInterface $assetLocation,
    protected QuickFormInstanceManagerInterface $quickFormManager,
  ) {
  }

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

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_asset_plant_edit_form_alter')]
  public function formAssetPlantEditFormAlter(array &$form, FormStateInterface $form_state, string $form_id): void {

    // Ensure we can get the EntityForm object.
    $form_object = $form_state->getFormObject();
    if (!$form_object instanceof EntityFormInterface) {
      return;
    }

    // Make sure the movement quick form is enabled.
    $quick_form = $this->quickFormManager->getInstance('movement');
    if ($quick_form === NULL || !$quick_form->status()) {
      return;
    }

    // Load the assets current location.
    $asset = $form_object->getEntity();
    $current_location = $this->assetLocation->getLocation($asset);
    $current_location_string = array_map(function (AssetInterface $location) {
      return $location->label();
    }, $current_location);

    // Add a wrapper for the current location fields.
    $form['rothamsted_current_location_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'style' => ['display: flex; flex-wrap: wrap; column-gap: 2em; align-items: center;'],
      ],
      '#weight' => $form['name']['#weight'] + 1,
      '#group' => 'location_field_group',
    ];

    // Add disabled textfield displaying the current location.
    $form['rothamsted_current_location_wrapper']['rothamsted_current_location'] = [
      '#type' => 'textfield',
      '#title' => new TranslatableMarkup('Current location'),
      '#description' => new TranslatableMarkup('The current location of the asset. This can be changed by creating a new movement log with the "Move asset" button.'),
      '#disabled' => TRUE,
      '#default_value' => $current_location_string,
    ];

    // Include the latest movement log in the field description.
    if ($latest_log = $this->assetLocation->getMovementLog($asset)) {
      $form['rothamsted_current_location_wrapper']['rothamsted_current_location']['#description'] .= ' ' . new TranslatableMarkup('Latest movement log: <a href=":uri">%log_label</a>', [':uri' => $latest_log->toUrl()->toString(), '%log_label' => $latest_log->label()]);
    }

    // Add button to move the asset.
    $form['rothamsted_current_location_wrapper']['rothamsted_current_location_move'] = [
      '#type' => 'submit',
      '#value' => new TranslatableMarkup('Move asset'),
      '#submit' => [[static::class, 'assetFormMoveSubmit']],
    ];

  }

  /**
   * Submit function for the current location form field.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function assetFormMoveSubmit(array &$form, FormStateInterface $form_state) {

    // Get the asset.
    /** @var \Drupal\asset\Entity\AssetInterface | NULL $asset */
    $asset = $form_state->getFormObject()->getEntity();
    if (empty($asset)) {
      return;
    }

    // Load the asset move action.
    $move_action = Action::load('asset_move_action');
    $move_action->execute([$asset]);

    // Redirect to the action confirm form route and set the destination
    // to come back to the asset canonical page.
    $operation_definition = $move_action->getPluginDefinition();
    if (!empty($operation_definition['confirm_form_route_name'])) {
      $options = [
        'query' => ['destination' => $asset->toUrl()->toString()],
      ];
      $form_state->setRedirect($operation_definition['confirm_form_route_name'], [], $options);
    }
  }

}
