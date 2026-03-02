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

}
