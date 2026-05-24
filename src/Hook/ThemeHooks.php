<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Theme hook implementations for farm_rothamsted.
 */
class ThemeHooks {

  /**
   * Implements hook_page_attachments().
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$attachments): void {
    $attachments['#attached']['library'][] = 'farm_rothamsted/farm_rothamsted_overrides';
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_field_multiple_value_form')]
  public function preprocessFieldMultipleValueForm(&$variables): void {

    // Move field description to after the field title in the table header.
    if (
      $variables['multiple']
      && !empty($variables['element']['#description'])
      && !empty($variables['element']['#title'])
    ) {
      $title = $variables['element']['#title'];
      $description = $variables['element']['#description'];

      // Update the field element title in $variables.
      // This is necessary because claro's preprocess hook uses the element
      // title and will always run after our hook because claro is a theme.
      // @see claro_preprocess_field_multiple_value_form.
      $variables['element']['#title'] = "$title: $description";
      unset($variables['description']);
    }
  }

}
