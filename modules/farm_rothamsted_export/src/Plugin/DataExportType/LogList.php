<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_export\Plugin\DataExportType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\farm_rothamsted_export\Attribute\DataExportType;

/**
 * Provides a log list data export.
 */
#[DataExportType(
  id: 'log_list',
  label: new TranslatableMarkup('Log list'),
  entity_type: 'log',
)]
class LogList extends EntityCsvListBase {

  /**
   * {@inheritdoc}
   */
  protected string $entityTypeId = 'log';

}
