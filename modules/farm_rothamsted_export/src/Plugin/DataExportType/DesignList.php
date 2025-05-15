<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_export\Plugin\DataExportType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\farm_rothamsted_export\Attribute\DataExportType;

/**
 * Provides a design list data export.
 */
#[DataExportType(
  id: 'rothamsted_design_list',
  label: new TranslatableMarkup('Design list'),
  entity_type: 'rothamsted_design',
)]
class DesignList extends EntityCsvListBase {

  /**
   * {@inheritdoc}
   */
  protected string $entityTypeId = 'rothamsted_design';

}
