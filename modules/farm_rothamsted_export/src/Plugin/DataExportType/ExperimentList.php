<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_export\Plugin\DataExportType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\farm_rothamsted_export\Attribute\DataExportType;

/**
 * Provides an experiment list data export.
 */
#[DataExportType(
  id: 'rothamsted_experiment_list',
  label: new TranslatableMarkup('Experiment list'),
  entity_type: 'rothamsted_experiment',
)]
class ExperimentList extends EntityCsvListBase {

  /**
   * {@inheritdoc}
   */
  protected string $entityTypeId = 'rothamsted_experiment';

}
