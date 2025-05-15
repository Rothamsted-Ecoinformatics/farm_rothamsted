<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_export\Plugin\DataExportType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\farm_rothamsted_export\Attribute\DataExportType;

/**
 * Provides an asset list data export.
 */
#[DataExportType(
  id: 'asset_list',
  label: new TranslatableMarkup('Asset list'),
  entity_type: 'asset',
)]
class AssetList extends DataExportTypeBase {

  /**
   * {@inheritdoc}
   */
  public function export(array $entities, array $config = []): array {

    $files = [];
    $output = $this->serializeEntities($entities, 'csv', 'asset');
    $filename = "asset-list-csv_export-" . date('c') . '.csv';
    if ($file = $this->saveFile('asset-list', $filename, $output)) {
      $files[] = $file->id();
    }

    return $files;
  }

}
