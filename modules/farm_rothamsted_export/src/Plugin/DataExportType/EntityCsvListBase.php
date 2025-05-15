<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_export\Plugin\DataExportType;

/**
 * Provides a data export class for simple CSV entity lists.
 */
abstract class EntityCsvListBase extends DataExportTypeBase {

  /**
   * The entity type ID.
   *
   * @var string
   */
  protected string $entityTypeId;

  /**
   * {@inheritdoc}
   */
  public function export(array $entities, array $config = []): array {

    $files = [];
    $output = $this->serializeEntities($entities, 'csv', $this->entityTypeId);
    $filename = "$this->entityTypeId-list-csv_export-" . date('c') . '.csv';
    if ($file = $this->saveFile("$this->entityTypeId-list", $filename, $output)) {
      $files[] = $file->id();
    }

    return $files;
  }

}
