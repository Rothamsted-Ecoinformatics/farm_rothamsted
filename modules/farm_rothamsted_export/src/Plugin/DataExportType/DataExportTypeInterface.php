<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_export\Plugin\DataExportType;

/**
 * Interface for Data Export Type plugins.
 */
interface DataExportTypeInterface {

  /**
   * Export data for specified entities.
   *
   * @param array $entities
   *   Array of entities to export.
   * @param array $config
   *   Optional array of configuration.
   *
   * @return array
   *   Array of file entity IDs.
   */
  public function export(array $entities, array $config = []): array;

  /**
   * Batch API callback function for exporting entities.
   *
   * @param array $entities
   *   Array of entities to export.
   * @param array $context
   *   The batch context.
   */
  public function processBatch(array $entities, array &$context): void;

}
