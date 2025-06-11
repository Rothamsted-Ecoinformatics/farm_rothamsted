<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_export\Plugin\DataExportType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\farm_rothamsted_export\Attribute\DataExportType;

/**
 * Provides a proposal list data export.
 */
#[DataExportType(
  id: 'rothamsted_proposal_list',
  label: new TranslatableMarkup('List of Proposals'),
  entity_type: 'rothamsted_proposal',
)]
class ProposalList extends EntityCsvListBase {

  /**
   * {@inheritdoc}
   */
  protected string $entityTypeId = 'rothamsted_proposal';

}
