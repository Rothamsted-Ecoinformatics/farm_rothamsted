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
  entity_type: 'rothamsted_proposal',
  label: new TranslatableMarkup('List of Proposals'),
)]
class ProposalList extends EntityCsvListBase {

  /**
   * {@inheritdoc}
   */
  protected string $entityTypeId = 'rothamsted_proposal';

}
