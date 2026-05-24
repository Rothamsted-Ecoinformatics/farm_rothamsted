<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_experiment\Plugin\Field\FieldFormatter;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'json' formatter.
 */
#[FieldFormatter(
  id: 'column_descriptors_tables',
  label: new TranslatableMarkup('Column descriptors tables'),
  field_types: [
    'json_native',
  ],
)]
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
      new TranslatableMarkup('ID'),
      new TranslatableMarkup('Name'),
      new TranslatableMarkup('Description'),
      new TranslatableMarkup('Quantity'),
      new TranslatableMarkup('Units'),
    ];

    // Build tables for each delta. In practice this is just one.
    foreach ($items as $delta => $item) {

      // Build a render array of factor tables.
      $tables = [];

      // Build table for each factor.
      $columns = Json::decode($item->value);
      foreach ($columns as $column) {

        // Protect against this formatter being used for other json fields.
        assert(!empty($column['column_name']), 'Column name not found in column_descriptor field.');
        assert(!empty($column['column_id']), 'Column id not found in column_descriptor field.');
        assert(!empty($column['column_levels']), 'Column levels not found in column_descriptor field.');

        // Include factor information in the table caption.
        $caption = [
          '#type' => 'div',
          'name' => [
            '#markup' => '<span class="name">' . $column['column_name'] . '</span>',
          ],
        ];

        // Render a link with the column ID if possible.
        // Some factor URLs are just identifiers for Rothamsted.
        try {
          $url = Url::fromUri($column['ontology_uri'] ?? '')->setAbsolute()->toString();
          $column_id = new TranslatableMarkup('<a href=":column_level_link">@column_id</a>', [':column_level_link' => $url, '@column_id' => $column['column_id']]);
        }
        catch (\Exception $e) {
          $column_id = $column['column_id'];
        }
        $caption['id'] = [
          '#markup' => '<span class="id">(' . $column_id . ')</span>',
        ];

        // Include description.
        if (!empty($column['column_description'])) {
          $caption['description'] = [
            '#markup' => '<span class="description">' . $column['column_description'] . '</span>',
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
            $column_level['level_id'],
            $column_level['level_name'],
            $column_level['level_description'],
            $column_level['quantity'] ?? '',
            $column_level['units'] ?? '',
          ];
        }, $column['column_levels'] ?? []);

        $tables[] = $table;
      }

      $elements[$delta] = $tables;
    }

    return $elements;
  }

}
