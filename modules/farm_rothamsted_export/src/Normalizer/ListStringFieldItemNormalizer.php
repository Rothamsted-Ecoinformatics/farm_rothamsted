<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_export\Normalizer;

use Drupal\options\Plugin\Field\FieldType\ListStringItem;
use Drupal\serialization\Normalizer\FieldItemNormalizer;

/**
 * Normalizes list string fields for farmOS CSV exports.
 */
class ListStringFieldItemNormalizer extends FieldItemNormalizer {

  /**
   * The supported format.
   */
  const FORMAT = 'csv';

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|null {
    /** @var \Drupal\options\Plugin\Field\FieldType\ListStringItem $field_item */

    // Return the list string option label if field_value_option_labels is set.
    if (isset($context['field_value_option_labels']) && $context['field_value_option_labels'] === TRUE) {
      $options = $field_item->getPossibleOptions();
      if (isset($options[$field_item->value])) {
        return (string) $options[$field_item->value];
      }
    }

    // Delegate to the parent method.
    return parent::normalize($field_item, $format, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, ?string $format = NULL, array $context = []): bool {
    return $data instanceof ListStringItem && $format == static::FORMAT;
  }

}
