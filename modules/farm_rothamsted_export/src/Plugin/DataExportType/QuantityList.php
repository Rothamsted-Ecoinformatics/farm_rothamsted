<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_export\Plugin\DataExportType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\farm_rothamsted_export\Attribute\DataExportType;

/**
 * Provides a quantity list data export.
 */
#[DataExportType(
  id: 'quantity_list',
  entity_type: 'quantity',
  label: new TranslatableMarkup('List of Quantities'),
)]
class QuantityList extends EntityCsvListBase {

  /**
   * {@inheritdoc}
   */
  protected string $entityTypeId = 'quantity';

}
