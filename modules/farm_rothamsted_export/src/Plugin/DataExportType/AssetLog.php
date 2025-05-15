<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_export\Plugin\DataExportType;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\farm_rothamsted_export\Attribute\DataExportType;
use Drupal\file\FileRepositoryInterface;
use Drupal\log\Entity\Log;
use Drupal\log\Entity\LogType;
use Drupal\quantity\Entity\Quantity;
use Drupal\quantity\Entity\QuantityType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Provides an asset log data export type plugin.
 */
#[DataExportType(
  id: 'asset_log',
  label: new TranslatableMarkup('Logs'),
  entity_type: 'asset',
)]
class AssetLog extends DataExportTypeBase {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityFieldManagerInterface $entityFieldManager,
    protected SerializerInterface $serializer,
    protected FileSystemInterface $fileSystem,
    protected FileRepositoryInterface $fileRepository,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
    protected AccountInterface $user,
    ConfigFactoryInterface $configFactory,
    protected Connection $database,
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $entityTypeManager,
      $entityFieldManager,
      $serializer,
      $fileSystem,
      $fileRepository,
      $fileUrlGenerator,
      $user,
      $configFactory,
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('serializer'),
      $container->get('file_system'),
      $container->get('file.repository'),
      $container->get('file_url_generator'),
      $container->get('current_user'),
      $container->get('config.factory'),
      $container->get('database'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function export(array $entities, array $config = []): array {

    // Build file array to return.
    $filename_prefix = $config['filename'] ?? '';
    $files = [];

    // Get entity IDs.
    $entity_ids = array_map(function ($entity) {
      return $entity->id();
    }, $entities);

    // Query log IDs. This is used to export both logs and quantities.
    $log_query = $this->entityTypeManager->getStorage('log')->getQuery()
      ->accessCheck()
      ->sort('timestamp', 'DESC')
      ->sort('id', 'DESC');
    if (isset($config['log_type'])) {
      $log_query->condition('type', $config['log_type']);
    }
    $asset_group = $log_query->orConditionGroup()
      ->condition('asset', $entity_ids, 'IN')
      ->condition('location', $entity_ids, 'IN');
    $log_query->condition($asset_group);
    $log_ids = $log_query->execute();

    // Export log list.
    if (isset($config['log_type'])) {

      // Load and export logs.
      $logs = Log::loadMultiple($log_ids);
      $output = $this->serializeEntities($logs, 'csv', 'log', $config['log_type']);

      // Add message if no data is returned.
      if (empty($output)) {
        $output = "No log data found.";
      }

      // Save to file.
      $filename = "$filename_prefix-asset-{$config['log_type']}-logs.csv";
      if ($file = $this->saveFile("$filename_prefix/asset-log", $filename, $output)) {
        $files[] = $file->id();
      }
    }

    // Export quantity list.
    if (isset($config['quantity_type'])) {

      $query = $this->database->select('quantity', 'q')
        ->fields('q', ['id']);
      $query->condition('q.type', $config['quantity_type']);

      // Join the {log_field_data} table (via reverse reference through
      // the {log__quantity} table).
      $query->join('log__quantity', 'lq', 'q.id = lq.quantity_target_id');
      $query->join('log_field_data', 'l', 'lq.entity_id = l.id');
      $query->condition('l.id', $log_ids, 'IN');
      $query->orderBy('l.timestamp', 'ASC');
      $query->orderBy('l.id', 'ASC');

      // Execute the query and return the results.
      $quantities = [];
      if ($quantity_ids = $query->execute()->fetchCol()) {
        $quantity_query = $this->entityTypeManager
          ->getStorage('quantity')
          ->getQuery()
          ->accessCheck()
          ->condition('id', $quantity_ids, 'IN');
        $quantities = Quantity::loadMultiple($quantity_query->execute());
      }
      $output = $this->serializeEntities($quantities, 'csv', 'quantity', $config['quantity_type']);

      // Add message if no data is returned.
      if (empty($output)) {
        $output = "No quantity data found.";
      }

      // Save to file.
      $filename = "$filename_prefix-quantity-{$config['quantity_type']}.csv";
      if ($file = $this->saveFile("$filename_prefix/asset-log", $filename, $output)) {
        $files[] = $file->id();
      }
    }

    return $files;
  }

  /**
   * {@inheritdoc}
   */
  public function processBatch(array $entities, array &$context): void {

    // Process each log type in separate step.
    if (empty($context['sandbox'])) {

      // Build config for each batch step.
      $default_config = $context['export_config'] ?? [];
      $batch_configs = [];

      // Include steps for each log type for exports of logs.
      foreach (array_keys(LogType::loadMultiple()) as $log_type) {
        $batch_configs[] = ['log_type' => $log_type] + $default_config;
      }

      // Include steps for each quantity type for exports of quantities.
      foreach (array_keys(QuantityType::loadMultiple()) as $quantity_type) {
        $batch_configs[] = ['quantity_type' => $quantity_type] + $default_config;
      }

      $context['sandbox']['batch_configs'] = $batch_configs;
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['total'] = count($batch_configs);
    }

    // Export data for next batch.
    $current = $context['sandbox']['progress'];
    $config = $context['sandbox']['batch_configs'][$current];
    $results = $this->export($entities, $config);
    $context['results']['files'] = array_merge($context['results']['files'] ?? [], $results);

    // Update finished progress.
    if (isset($config['log_type'])) {
      $context['message'] = new TranslatableMarkup('Exported @log_type logs', ['@log_type' => $config['log_type']]);
    }
    if (isset($config['quantity_type'])) {
      $context['message'] = new TranslatableMarkup('Exported @quantity_type quantities', ['@quantity_type' => $config['quantity_type']]);
    }

    $context['sandbox']['progress']++;
    if ($context['sandbox']['progress'] != $context['sandbox']['total']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['total'];
    }
  }

}
