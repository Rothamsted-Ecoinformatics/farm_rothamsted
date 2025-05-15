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

    // Serialize entities.
    $output = $this->serializeEntities($entities, 'csv', $this->entityTypeId);

    // Save to file.
    $filename_prefix = $config['filename'] ?? '';
    $filename = "$filename_prefix-$this->entityTypeId-list.csv";
    $files = [];
    if ($file = $this->saveFile("$filename_prefix/$this->entityTypeId-list", $filename, $output)) {
      $files[] = $file->id();
    }

    return $files;
  }

}
