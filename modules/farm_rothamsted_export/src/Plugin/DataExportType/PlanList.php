<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_export\Plugin\DataExportType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\farm_rothamsted_export\Attribute\DataExportType;

/**
 * Provides a plan list data export.
 */
#[DataExportType(
  id: 'plan_list',
  entity_type: 'plan',
  label: new TranslatableMarkup('List of Plans'),
)]
class PlanList extends EntityCsvListBase {

  /**
   * {@inheritdoc}
   */
  protected string $entityTypeId = 'plan';

}
