<?php

namespace Drupal\farm_rothamsted_experiment\Plugin\Field\FieldFormatter;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Url;
use function PHPUnit\Framework\assertNotEmpty;

/**
 * Plugin implementation of the 'json' formatter.
 *
 * @FieldFormatter(
 *   id = "treatment_factors_tables",
 *   label = @Translation("Treatment factors tables"),
 *   field_types = {
 *     "json_native",
 *   },
 * )
 */
class TreatmentFactorsTables extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [
      '#attached' => [
        'library' => ['farm_rothamsted_experiment/treatment_factors_tables.formatter'],
      ],
    ];

    // Build the table header once.
    $table_header = [
      $this->t('ID'),
      $this->t('Name'),
      $this->t('Description'),
      $this->t('Quantity'),
      $this->t('Units'),
    ];

    // Build tables for each delta. In practice this is just one.
    foreach ($items as $delta => $item) {

      // Build a render array of factor tables.
      $factor_tables = [];

      // Build table for each factor.
      $factors = Json::decode($item->value);
      foreach ($factors as $factor) {

        // Protect against this formatter being used for other json fields.
        assertNotEmpty($factor['name']);
        assertNotEmpty($factor['id']);
        assertNotEmpty($factor['factor_levels']);

        // Include factor information in the table caption.
        $caption = [
          '#type' => 'div',
          'name' => [
            '#markup' => '<span class="name">' . $factor['name'] . '</span>',
          ],
        ];

        // Render a link with the factor ID if possible.
        // Some factor URLs are just identifiers for Rothamsted.
        try {
          $url = Url::fromUri($factor['uri'] ?? '')->setAbsolute()->toString();
          $factor_id = $this->t('<a href=":factor_level_link">@factor_id</a>', [':factor_level_link' => $url, '@factor_id' => $factor['id']]);
        }
        catch (\Exception $e) {
          $factor_id = $factor['id'];
        }
        $caption['id'] = [
          '#markup' => '<span class="id">(' . $factor_id . ')</span>',
        ];

        // Include description.
        if (!empty($factor['description'])) {
          $caption['description'] = [
            '#markup' => '<span class="description">' . $factor['description'] . '</span>',
          ];
        }

        // Build table.
        $factor_table = [
          '#type' => 'table',
          '#caption' => $caption,
          '#header' => $table_header,
          '#attributes' => [
            'class' => ['treatment-factor-level-table'],
          ],
        ];

        // Add row for each factor level.
        $factor_table['#rows'] = array_map(function ($factor_level) {
          return [
            $factor_level['id'],
            $factor_level['name'],
            $factor_level['description'],
            $factor_level['quantity'] ?? '',
            $factor_level['units'] ?? '',
          ];
        }, $factor['factor_levels'] ?? []);

        $factor_tables[] = $factor_table;
      }

      $elements[$delta] = $factor_tables;
    }

    return $elements;
  }

}
