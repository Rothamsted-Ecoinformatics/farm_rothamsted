<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_export\Form;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Checkboxes;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\farm_rothamsted_export\DataExportTypePluginManager;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for configuring and confirming data export.
 */
class ExportDataActionForm extends ConfirmFormBase {

  /**
   * The tempstore object.
   *
   * @var \Drupal\Core\TempStore\SharedTempStore
   */
  protected $tempStore;

  public function __construct(
    protected AccountInterface $currentUser,
    protected ClassResolverInterface $classResolver,
    protected RouteMatchInterface $currentRouteMatch,
    protected DataExportTypePluginManager $dataExportTypePluginManager,
    PrivateTempStoreFactory $tempStore,
  ) {
    $this->tempStore = $tempStore->get('export_data_action');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('class_resolver'),
      $container->get('current_route_match'),
      $container->get('farm_rothamsted_export.data_export_type_plugin_manager'),
      $container->get('tempstore.private'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'farm_rothamsted_export_data_action_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Export selected entities');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Choose an export type and configure export settings.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    // Adjust this to a suitable return URL.
    return new Url('system.admin_content');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Retrieve the entities from tempstore.
    $entity_type_id = $this->currentRouteMatch->getParameter('entity_type');
    $entities = $this->tempStore->get("{$this->currentUser->id()}:$entity_type_id");
    if (empty($entities)) {
      $this->messenger()->addError($this->t('No entities selected for export.'));
      return $this->redirect('system.admin_content');
    }

    // Get all available export type plugins.
    $export_types = $this->dataExportTypePluginManager->getDefinitions();

    // Prepare export type options.
    $export_type_options = [];
    foreach ($export_types as $plugin_id => $plugin_definition) {
      if ($plugin_definition['entity_type'] === $entity_type_id) {
        $export_type_options[$plugin_id] = $plugin_definition['label'];
      }
    }
    $form['export_type'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Export Type'),
      '#description' => $this->t('Choose the export type for your entities.'),
      '#options' => $export_type_options,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Build tempstore ID.
    $entity_type_id = $this->currentRouteMatch->getParameter('entity_type');
    $tempstore_id = "{$this->currentUser->id()}:$entity_type_id";

    // Build batch operations.
    $plugins = Checkboxes::getCheckedCheckboxes($form_state->getValue('export_type'));
    $operations = array_map(function ($plugin_id) use ($tempstore_id) {
      return [
        [self::class, 'processPluginBatch'],
        [$tempstore_id, $plugin_id],
      ];
    }, $plugins);

    // Set batch.
    $batch = [
      'operations' => $operations,
      'finished' => [self::class, 'batchFinished'],
      'title' => $this->t('Exporting data'),
      'init_message' => $this->t('Exporting data...'),
      'progress_message' => $this->t('Exporting data...'),
      'error_message' => $this->t('Error exporting data.'),
    ];
    batch_set($batch);
  }

  /**
   * Batch callback to process each plugin.
   *
   * @param string $entity_cache_id
   *   The temporary cache ID that contains the entities to process.
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $context
   *   Batch context.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public static function processPluginBatch(string $entity_cache_id, string $plugin_id, array &$context) {

    // Create the export plugin instance.
    /** @var \Drupal\farm_rothamsted_export\DataExportTypePluginManager $export_plugin_manager */
    $export_plugin_manager = \Drupal::service('farm_rothamsted_export.data_export_type_plugin_manager');
    $export_plugin = $export_plugin_manager->createInstance($plugin_id);

    // Load entities.
    $temp_store = \Drupal::service('tempstore.private')->get('export_data_action');
    $entities = $temp_store->get($entity_cache_id);

    // Delegate to plugin processBatch function.
    $export_plugin->processBatch($entities, $context);
  }

  /**
   * Batch callback to finish export and wrap in Zip file.
   *
   * @param bool $success
   *   The batch success.
   * @param array $results
   *   Array of batch results.
   */
  public static function batchFinished(bool $success, array $results): void {

    if (!$success) {
      // Check operations.
    }

    $file_system = \Drupal::service('file_system');

    // Prepare the file directory.
    $scheme = \Drupal::configFactory()->get('system.file')->get('default_scheme') ?? 'public';
    $directory = "$scheme://export-data";
    $file_system->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);

    // Open zip archive.
    $zip = new \ZipArchive();
    $zip_private_path = $directory . '/export-' . date('c') . '.zip';
    $zip_filename = $file_system->realpath($zip_private_path);
    $result = $zip->open($zip_filename, constant('ZipArchive::CREATE'));
    if ($result !== TRUE) {
      \Drupal::logger('farm_rothamsted_export')->warning("Zip archive could not be created. Error code: $result");
    }

    // Add result files to zip.
    $files = File::loadMultiple($results);
    foreach ($files as $file) {
      $filepath = $file_system->realpath($file->getFileUri());
      $result = $zip->addFile($filepath, basename($file->getFileUri()));
      if (!$result) {
        \Drupal::logger('farm_rothamsted_export')->warning('File could not be added to zip archive.');
      }
    }

    // Close zip archive.
    $result = $zip->close();
    if (!$result) {
      \Drupal::logger('farm_rothamsted_export')->warning('Zip archive could not be closed.');
    }

    // Create file entity for zip.
    $zip_file = File::create([
      'uri' => $zip_private_path,
      'status' => 0,
      'uid' => \Drupal::currentUser()->id(),
    ]);
    $zip_file->save();

    // Build URL to file.
    $file_url_generator = \Drupal::service('file_url_generator');
    $url = $file_url_generator->generateAbsoluteString($zip_file->getFileUri());

    // Add success message.
    \Drupal::messenger()->addMessage(new TranslatableMarkup(
        'ZIP file created: <a href=":url">%filename</a>', [
          ':url' => $url,
          '%filename' => "export",
        ]),
    );
  }

}
