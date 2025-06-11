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
  label: new TranslatableMarkup('List of Assets'),
  entity_type: 'asset',
)]
class AssetList extends EntityCsvListBase {

  /**
   * {@inheritdoc}
   */
  protected string $entityTypeId = 'asset';

}
