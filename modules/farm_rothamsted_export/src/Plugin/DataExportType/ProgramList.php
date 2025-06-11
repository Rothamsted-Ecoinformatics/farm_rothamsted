<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_export\Plugin\DataExportType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\farm_rothamsted_export\Attribute\DataExportType;

/**
 * Provides a program list data export.
 */
#[DataExportType(
  id: 'rothamsted_program_list',
  label: new TranslatableMarkup('List of Programs'),
  entity_type: 'rothamsted_program',
)]
class ProgramList extends EntityCsvListBase {

  /**
   * {@inheritdoc}
   */
  protected string $entityTypeId = 'rothamsted_program';

}
