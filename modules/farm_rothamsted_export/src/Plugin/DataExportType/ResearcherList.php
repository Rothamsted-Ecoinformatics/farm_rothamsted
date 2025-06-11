<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_export\Plugin\DataExportType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\farm_rothamsted_export\Attribute\DataExportType;

/**
 * Provides a researcher list data export.
 */
#[DataExportType(
  id: 'rothamsted_researcher_list',
  entity_type: 'rothamsted_researcher',
  label: new TranslatableMarkup('List of Researchers'),
)]
class ResearcherList extends EntityCsvListBase {

  /**
   * {@inheritdoc}
   */
  protected string $entityTypeId = 'rothamsted_researcher';

}
