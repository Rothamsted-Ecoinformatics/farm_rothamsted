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
 *   id = "column_descriptors_tables",
 *   label = @Translation("Column descriptors tables"),
 *   field_types = {
 *     "json_native",
 *   },
 * )
 */
class ColumnDescriptorsTables extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [
      '#attached' => [
        'library' => ['farm_rothamsted_experiment/column_descriptors_tables.formatter'],
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
      $tables = [];

      // Build table for each factor.
      $columns = Json::decode($item->value);
      foreach ($columns as $column) {

        // Protect against this formatter being used for other json fields.
//        assertNotEmpty($factor['name']);
//        assertNotEmpty($factor['id']);
//        assertNotEmpty($factor['factor_levels']);

        // Include factor information in the table caption.
        $caption = [
          '#type' => 'div',
          'name' => [
            '#markup' => '<span class="name">' . $column['name'] . '</span>',
          ],
        ];

        // Render a link with the column ID if possible.
        // Some factor URLs are just identifiers for Rothamsted.
        try {
          $url = Url::fromUri($column['uri'] ?? '')->setAbsolute()->toString();
          $column_id = $this->t('<a href=":column_level_link">@column_id</a>', [':column_level_link' => $url, '@column_id' => $column['id']]);
        }
        catch (\Exception $e) {
          $column_id = $column['id'];
        }
        $caption['id'] = [
          '#markup' => '<span class="id">(' . $column_id . ')</span>',
        ];

        // Include description.
        if (!empty($column['description'])) {
          $caption['description'] = [
            '#markup' => '<span class="description">' . $column['description'] . '</span>',
          ];
        }

        // Build table.
        $table = [
          '#type' => 'table',
          '#caption' => $caption,
          '#header' => $table_header,
          '#attributes' => [
            'class' => ['column-level-table'],
          ],
        ];

        // Add row for each factor level.
        $table['#rows'] = array_map(function ($column_level) {
          return [
            $column_level['id'],
            $column_level['name'],
            $column_level['description'],
            $column_level['quantity'] ?? '',
            $column_level['units'] ?? '',
          ];
        }, $column['factor_levels'] ?? []);

        $tables[] = $table;
      }

      $elements[$delta] = $tables;
    }

    return $elements;
  }

}
