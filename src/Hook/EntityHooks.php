<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\farm_field\FarmFieldFactoryInterface;
use Drupal\inline_entity_form\Plugin\Field\FieldWidget\InlineEntityFormComplex;

/**
 * Entity hook implementations for farm_rothamsted.
 */
class EntityHooks {

  public function __construct(
    protected readonly FarmFieldFactoryInterface $farmFieldFactory,
  ) {
  }

  /**
   * Implements hook_farm_entity_bundle_field_info().
   */
  #[Hook('farm_entity_bundle_field_info')]
  public function farmEntityBundleFieldInfo(EntityTypeInterface $entity_type, string $bundle) {
    $fields = [];

    // Add fields to harvest logs.
    if ($entity_type->id() === 'log' && $bundle === 'harvest') {

      // Add storage_location field.
      $field_info = [
        'type' => 'entity_reference',
        'label' => new TranslatableMarkup('Storage location'),
        'description' => new TranslatableMarkup('The harvest storage location.'),
        'target_type' => 'asset',
        'target_bundle' => 'structure',
        'multiple' => TRUE,
        'weight' => [
          'form' => 90,
          'view' => 90,
        ],
      ];
      $fields['storage_location'] = $this->farmFieldFactory->bundleFieldDefinition($field_info);
    }

    // Add fields to input logs.
    if ($entity_type->id() === 'log' && $bundle === 'input') {

      // Add COSSH Hazard field.
      $options = [
        'type' => 'list_string',
        'label' => new TranslatableMarkup('COSSH Hazard Assessments'),
        'description' => new TranslatableMarkup('The COSHH assessments which need to be considered when handling fertilisers.'),
        'allowed_values_function' => 'farm_rothamsted_cossh_hazard_field_allowed_values',
        'multiple' => TRUE,
        'weight' => [
          'form' => -50,
          'view' => -50,
        ],
      ];
      $fields['cossh_hazard'] = $this->farmFieldFactory->bundleFieldDefinition($options);

      // Add PPE field.
      $options = [
        'type' => 'list_string',
        'label' => new TranslatableMarkup('PPE'),
        'description' => new TranslatableMarkup('The protective clothing and equipment required for a specific job. Select all that apply to confirm they have been used.'),
        'allowed_values_function' => 'farm_rothamsted_ppe_field_allowed_values',
        'multiple' => TRUE,
        'weight' => [
          'form' => -50,
          'view' => -50,
        ],
      ];
      $fields['ppe'] = $this->farmFieldFactory->bundleFieldDefinition($options);

    }

    return $fields;
  }

  /**
   * Implements hook_entity_base_field_info_alter().
   */
  #[Hook('entity_base_field_info_alter')]
  public function entityBaseFieldInfoAlter(&$fields, EntityTypeInterface $entity_type) {

    // Keep quantity entities when deleting references to log quantities.
    // Modify behavior from https://github.com/farmOS/farmOS/pull/877.
    if ($entity_type->id() === 'log' && isset($fields['quantity'])) {
      $form_options = $fields['quantity']->getDisplayOptions('form');
      if ($form_options['type'] === 'inline_entity_form_complex') {
        $form_options['settings']['removed_reference'] = InlineEntityFormComplex::REMOVED_KEEP;
        $fields['quantity']->setDisplayOptions('form', $form_options);
      }
    }
  }

  /**
   * Implements hook_entity_field_access().
   */
  #[Hook('entity_field_access')]
  public function entityFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, ?FieldItemListInterface $items = NULL) {
    // Add field access check to prevent users from changing username and email.
    if ($operation == 'edit' && $field_definition->getTargetEntityTypeId() == 'user' && in_array($field_definition->getName(), ['name', 'mail'])) {
      return AccessResult::forbiddenIf(!$account->hasPermission('administer users'));
    }
    return AccessResult::neutral();
  }

}
