<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_export;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\farm_rothamsted_export\Attribute\DataExportType;
use Drupal\farm_rothamsted_export\Plugin\DataExportType\DataExportTypeInterface;

/**
 * Manages discovery and instantiation of Data Export Type plugins.
 */
class DataExportTypePluginManager extends DefaultPluginManager {

  /**
   * Constructs a DataExportTypePluginManager object.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/DataExportType',
      $namespaces,
      $module_handler,
      DataExportTypeInterface::class,
      DataExportType::class,
    );
    $this->alterInfo('data_export_type_info');
    $this->setCacheBackend($cache_backend, 'data_export_type_info');
  }

}
