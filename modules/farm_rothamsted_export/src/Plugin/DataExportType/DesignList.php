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
  entity_type: 'rothamsted_design',
  label: new TranslatableMarkup('List of Designs'),
  description: new TranslatableMarkup('A .csv which lists of all the items you selected. Each item you selected will appear in the .csv is a row. Each column in the .csv is field pre-filled with the data about each item (cells are left blank if no data exists). Please note that this will only export the current version of the data. Information associated with previous revisions is not included.'),
)]
class DesignList extends EntityCsvListBase {

  /**
   * {@inheritdoc}
   */
  protected string $entityTypeId = 'rothamsted_design';

}
