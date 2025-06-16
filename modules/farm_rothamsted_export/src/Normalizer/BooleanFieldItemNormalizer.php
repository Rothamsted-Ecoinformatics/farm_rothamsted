<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_export\Normalizer;

use Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem;
use Drupal\serialization\Normalizer\FieldItemNormalizer;

/**
 * Normalizes boolean fields for farmOS CSV exports.
 */
class BooleanFieldItemNormalizer extends FieldItemNormalizer {

  /**
   * The supported format.
   */
  const FORMAT = 'csv';

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|null {
    /** @var \Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem $field_item */

    // Return boolean label if field_value_option_labels is set.
    if (isset($context['field_value_option_labels']) && $context['field_value_option_labels'] === TRUE) {
      $setting = $field_item->value ?
        $field_item->getFieldDefinition()->getSetting('on_label') :
        $field_item->getFieldDefinition()->getSetting('off_label');
      if ($setting) {
        return (string) $setting;
      }
    }

    // Delegate to the parent method.
    return parent::normalize($field_item, $format, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, ?string $format = NULL, array $context = []): bool {
    return $data instanceof BooleanItem && $format == static::FORMAT;
  }

}
