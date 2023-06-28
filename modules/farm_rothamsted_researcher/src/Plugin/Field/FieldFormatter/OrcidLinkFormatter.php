<?php

namespace Drupal\farm_rothamsted_researcher\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'rothamsted_orcid_link' formatter.
 *
 * @FieldFormatter(
 *   id = "rothamsted_orcid_link",
 *   label = @Translation("Orcid Link"),
 *   field_types = {
 *     "string",
 *   }
 * )
 */
class OrcidLinkFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      if (!$item->isEmpty()) {
        $elements[$delta] = [
          '#type' => 'link',
          '#url' => Url::fromUri("https://orcid.org/$item->value"),
          '#title' => $item->value,
        ];
      }
    }

    return $elements;
  }

}
