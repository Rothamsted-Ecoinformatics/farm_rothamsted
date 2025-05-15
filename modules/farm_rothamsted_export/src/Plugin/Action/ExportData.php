<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_export\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Action\Plugin\Action\EntityActionBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\farm_rothamsted_export\DataExportTypePluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an action for exporting entity data.
 */
#[Action(
  id: 'export_data',
  label: new TranslatableMarkup('Export Data'),
  confirm_form_route_name: 'entity.asset.export_data_action_form',
  type: 'asset',
)]
class ExportData extends EntityActionBase {

  /**
   * The tempstore object.
   *
   * @var \Drupal\Core\TempStore\SharedTempStore
   */
  protected $tempStore;

  /**
   * Constructs a new ExportDataAction object.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entityTypeManager,
    PrivateTempStoreFactory $tempStore,
    protected AccountInterface $currentUser,
    protected DataExportTypePluginManager $dataExportTypePluginManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entityTypeManager);
    $this->tempStore = $tempStore->get('export_data_action');
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
      $container->get('tempstore.private'),
      $container->get('current_user'),
      $container->get('farm_rothamsted_export.data_export_type_plugin_manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    // Store entities for export in temp store.
    $this->tempStore->set($this->currentUser->id() . ':asset', $entities);
  }

  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {
    $this->executeMultiple([$object]);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('view', $account, $return_as_object);
  }

}
