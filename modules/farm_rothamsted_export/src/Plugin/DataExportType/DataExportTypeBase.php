<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_export\Plugin\DataExportType;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\FileInterface;
use Drupal\file\FileRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Base class for Data Export Type plugins.
 */
abstract class DataExportTypeBase extends PluginBase implements DataExportTypeInterface, ContainerFactoryPluginInterface {

  /**
   * The default file scheme.
   *
   * @var string
   */
  protected $defaultFileScheme;

  /**
   * The entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The entities to export.
   *
   * @var \Drupal\Core\Entity\EntityInterface[]
   */
  protected $entities;

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
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->defaultFileScheme = $configFactory->get('system.file')->get('default_scheme') ?? 'public';
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
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processBatch(array $entities, array &$context): void {
    $results = $this->export($entities, $context['export_config'] ?? []);
    $context['results']['files'] = array_merge($context['results']['files'] ?? [], $results);
    $context['message'] = 'Processed ' . $this->getPluginId();
  }

  /**
   * Helper function to serialize an array of entities to given format.
   *
   * @param array $entities
   *   Array of entities.
   * @param string $format
   *   The serialization format.
   * @param string $entity_type_id
   *   The entity type ID to load columns for.
   * @param string|null $bundle
   *   The optional entity type bundle to load columns for.
   * @param array $context
   *   Additional context for the serialization.
   *
   * @return string
   *   The serialization result.
   */
  protected function serializeEntities(array $entities, string $format, string $entity_type_id, ?string $bundle = NULL, array $context = []): string {

    // If no bundle is specified determine which entity bundles are represented.
    $bundles = [];
    if (!$bundle && $this->entityTypeManager->getDefinition($entity_type_id)->hasKey('bundle')) {
      foreach ($entities as $entity) {
        if (!in_array($entity->bundle(), $bundles)) {
          $bundles[] = $entity->bundle();
        }
      }
    }
    if (!$bundle && count($bundles) === 1) {
      $bundle = $bundles[0];
    }

    // Serialize the entities with the csv format.
    $default_context = [

      // Define the columns to include.
      'include_columns' => $this->getIncludeColumns($entity_type_id, $bundle),

      // Return processed text, if desired. Otherwise, raw user input will be
      // exported.
      'processed_text' => FALSE,

      // Return content entity labels and config entity IDs.
      'content_entity_labels' => TRUE,
      'config_entity_ids' => TRUE,

      // Field value option labels.
      'field_value_option_labels' => TRUE,

      // Return RFC3339 dates.
      'rfc3339_dates' => TRUE,

      // Return WKT geometry.
      'wkt' => TRUE,

      // CSV encoder settings.
      'csv_settings' => [
        'sanitize' => TRUE,
        'strip_tags' => FALSE,
      ],
    ];
    return $this->serializer->serialize(array_values($entities), $format, array_merge($context, $default_context));
  }

  /**
   * Helper function to save content to a temporary file entity.
   *
   * @param string $directory
   *   The file directory without scheme prefix.
   * @param string $filename
   *   The filename.
   * @param string $content
   *   The file content.
   *
   * @return \Drupal\file\FileInterface|null
   *   The saved file entity or NULL if failure.
   */
  protected function saveFile(string $directory, string $filename, string $content): ?FileInterface {

    // Prepare the file directory.
    $write_directory = "$this->defaultFileScheme://export-data/$directory";
    $this->fileSystem->prepareDirectory($write_directory, FileSystemInterface::CREATE_DIRECTORY);

    // Create the file.
    $destination = "$write_directory/$filename";
    $destination = $this->fileSystem->getDestinationFilename($destination, FileExists::Rename);
    try {
      $file = $this->fileRepository->writeData($content, $destination);
    }

    // If file creation failed, bail with a warning.
    catch (\Exception $e) {
      $this->messenger()->addWarning($this->t('Could not create file.'));
      return NULL;
    }

    // Make the file temporary.
    $file->set('status', 0);
    $file->save();
    return $file;
  }

  /**
   * Get a list of columns to include in CSV exports.
   *
   * Copied from farmOS core EntityCsvActionForm.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string|null $bundle
   *   If specified, columns that are specific to this bundle will be included.
   *
   * @return string[]
   *   An array of column names.
   */
  protected function getIncludeColumns(string $entity_type_id, ?string $bundle = NULL) {

    // Start with ID and UUID.
    $columns = [
      'id',
      'uuid',
    ];

    // Define which field types are supported.
    $supported_field_types = [
      'boolean',
      'created',
      'changed',
      'entity_reference',
      'fraction',
      'geofield',
      'list_string',
      'state',
      'string',
      'text_long',
      'timestamp',
    ];

    // Add base field for supported field types.
    $base_field_definitions = $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id);
    foreach ($base_field_definitions as $field_name => $field_definition) {
      if (!in_array($field_name, $columns) && in_array($field_definition->getType(), $supported_field_types)) {
        $columns[] = $field_name;
      }
    }

    // Add bundle fields for supported field types.
    if ($bundle) {
      if ($this->entityTypeManager->hasHandler($entity_type_id, 'bundle_plugin')) {
        $bundle_fields = $this->entityTypeManager
          ->getHandler($entity_type_id, 'bundle_plugin')
          ->getFieldDefinitions($bundle);
        foreach ($bundle_fields as $field_name => $field_definition) {
          if (!in_array($field_name, $columns) && in_array($field_definition->getType(), $supported_field_types)) {
            $columns[] = $field_name;
          }
        }
      }
    }

    // Remove revision and language columns.
    $remove_columns = [
      'default_langcode',
      'revision_translation_affected',
      'revision_created',
      'revision_user',
      'revision_default',
    ];
    $columns = array_filter($columns, function ($name) use ($remove_columns) {
      return !in_array($name, $remove_columns);
    });

    return $columns;
  }

}
