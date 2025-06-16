<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_export\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines an attribute for Data Export Type plugins.
 */
#[\Attribute] #[Attribute(Attribute::TARGET_CLASS)]
class DataExportType extends Plugin {

  /**
   * Constructs a new DataExportType attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param string $entity_type
   *   The supported entity type.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The label for the export type.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $description
   *   An optional description for the export type.
   */
  public function __construct(
    public readonly string $id,
    public readonly string $entity_type,
    public readonly TranslatableMarkup $label,
    public readonly ?TranslatableMarkup $description = NULL,
  ) {}

}
