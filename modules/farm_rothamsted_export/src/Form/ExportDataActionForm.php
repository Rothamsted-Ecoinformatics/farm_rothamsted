<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_export\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Checkboxes;
use Drupal\Core\Render\Markup;
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
    protected EntityTypeManagerInterface $entityTypeManager,
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
      $container->get('entity_type.manager'),
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
    return $this->t('Export data');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Choose data types to export. A zip file will be created with the specified export filename.');
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
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $entities = $this->tempStore->get("{$this->currentUser->id()}:$entity_type_id");
    if (empty($entities)) {
      $this->messenger()->addError($this->t('No entities selected for export.'));
      return $this->redirect('system.admin_content');
    }

    // Filter out entities the user doesn't have access to.
    $inaccessible_entities = [];
    $accessible_entities = [];
    foreach ($entities as $entity) {
      if (!$this->checkEntityAccess($entity, $this->currentUser())) {
        $inaccessible_entities[] = $entity;
        continue;
      }
      $accessible_entities[] = $entity;
    }

    // Add warning message for inaccessible entities.
    if (!empty($inaccessible_entities)) {
      $this->messenger()->addWarning(new TranslatableMarkup(
        'You do not have permission to export data from the below @count @entity_type because you are not named as a Researcher on the @entity_type.',
        [
          '@count' => count($inaccessible_entities),
          '@entity_type' => $entity_type->getCollectionLabel(),
        ],
      ));
      foreach ($inaccessible_entities as $entity) {
        $this->messenger()->addWarning(Markup::create("<a href=\"{$entity->toUrl()->toString()}\">{$entity->label()}</a>"));
      }
    }

    // Update tempstore to only the accessible entities.
    $this->tempStore->set("{$this->currentUser->id()}:$entity_type_id", $accessible_entities);

    // Get all available export type plugins.
    $export_types = $this->dataExportTypePluginManager->getDefinitions();
    ksort($export_types);

    // Prepare export type options.
    $export_type_options = [];
    foreach ($export_types as $plugin_id => $plugin_definition) {
      if ($plugin_definition['entity_type'] === $entity_type_id) {
        $export_type_options[$plugin_id] = $plugin_definition['label'];
      }
    }

    $form['export_type'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Data types'),
      '#options' => $export_type_options,
      '#default_value' => array_keys($export_type_options),
      '#required' => TRUE,
    ];

    // Add descriptions to checkboxes.
    foreach ($export_types as $plugin_id => $plugin_definition) {
      if ($plugin_definition['description']) {
        $form['export_type'][$plugin_id]['#description'] = $plugin_definition['description'];
      }
    }

    $default_name = date('Y-m-d_H-i-s');
    $form['filename'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Export filename'),
      '#required' => TRUE,
      '#default_value' => $default_name,
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
    $filename = $form_state->getValue('filename');
    $plugins = Checkboxes::getCheckedCheckboxes($form_state->getValue('export_type'));
    $operations = array_map(function ($plugin_id) use ($tempstore_id, $filename) {
      return [
        [self::class, 'processPluginBatch'],
        [$tempstore_id, $filename, $plugin_id],
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
   * @param string $filename
   *   The filename prefix.
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $context
   *   Batch context.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public static function processPluginBatch(string $entity_cache_id, string $filename, string $plugin_id, array &$context) {

    // Create the export plugin instance.
    /** @var \Drupal\farm_rothamsted_export\DataExportTypePluginManager $export_plugin_manager */
    $export_plugin_manager = \Drupal::service('farm_rothamsted_export.data_export_type_plugin_manager');
    $export_plugin = $export_plugin_manager->createInstance($plugin_id);

    // Load entities.
    $temp_store = \Drupal::service('tempstore.private')->get('export_data_action');
    $entities = $temp_store->get($entity_cache_id);

    // Save filename to context.
    $context['export_config']['filename'] = $filename;
    $context['results']['filename'] = $filename;

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

    // Build filename.
    $zip_name = $results['filename'];
    $zip_filename = "$zip_name.zip";

    // Prepare the file directory.
    $file_system = \Drupal::service('file_system');
    $scheme = \Drupal::configFactory()->get('system.file')->get('default_scheme') ?? 'public';
    $directory = "$scheme://export-data/$zip_name";
    $file_system->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);

    // Open zip archive.
    $zip = new \ZipArchive();
    $zip_path = "$directory/$zip_filename";
    $zip_path = $file_system->getDestinationFilename($zip_path, FileExists::Rename);
    $zip_real_path = $file_system->realpath($zip_path);
    $result = $zip->open($zip_real_path, constant('ZipArchive::CREATE'));
    if ($result !== TRUE) {
      \Drupal::logger('farm_rothamsted_export')->warning("Zip archive could not be created. Error code: $result");
    }

    // Add result files to zip.
    if (!empty($results['files'])) {
      $files = File::loadMultiple($results['files']);
      foreach ($files as $file) {
        $filepath = $file_system->realpath($file->getFileUri());
        $result = $zip->addFile($filepath, basename($file->getFileUri()));
        if (!$result) {
          \Drupal::logger('farm_rothamsted_export')->warning('File could not be added to zip archive.');
        }
      }
    }

    // Close zip archive.
    $result = $zip->close();
    if (!$result) {
      \Drupal::logger('farm_rothamsted_export')->warning('Zip archive could not be closed.');
    }

    // Create file entity for zip.
    $zip_file = File::create([
      'uri' => $zip_path,
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
          '%filename' => $zip_filename,
        ]),
    );
  }

  /**
   * Helper function to check entity access with researcher logic.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The user account.
   *
   * @return bool
   *   Boolean access result.
   */
  protected function checkEntityAccess(EntityInterface $entity, ?AccountInterface $account = NULL) {

    # Check access based on user roles.
    $result = AccessResult::forbidden();
    $roles = $account->getRoles();
    $all_access_roles = [
      'rothamsted_data_admin',
      'rothamsted_farm_manager',
    ];
    $research_assigned_roles = [
      'rothamsted_research_lead',
      'rothamsted_research_editor',
    ];

    # Allow access if user has all access roles.
    if (!empty(array_intersect($roles, $all_access_roles))) {
      $result = AccessResult::allowed();
    }

    # If user has a researcher role, allow access if they have update access.
    # In many cases this will delegate to research access logic that uses the
    # "update research_assigned {entity_type}" permission.
    elseif (!empty(array_intersect($roles, $research_assigned_roles))) {
      $result = $entity->access('update', $account, TRUE);
    }

    return $result->isAllowed();
  }

}
