<?php

namespace Drupal\farm_rothamsted_experiment_research;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity\EntityPermissionProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides research_assigned permissions for entity types.
 */
class ResearchPermissionProvider implements EntityPermissionProviderInterface, ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs a TaxonomyViewsIntegratorPermissions instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->entityTypeManager = $entity_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
    );
  }

  /**
   * Build plan permissions.
   *
   * @return array
   *   Permissions array.
   */
  public function planPermissions(): array {
    return $this->buildPermissions($this->entityTypeManager->getStorage('plan')->getEntityType());
  }

  /**
   * Build asset permissions.
   *
   * @return array
   *   Permissions array.
   */
  public function assetPermissions(): array {
    return $this->buildPermissions($this->entityTypeManager->getStorage('asset')->getEntityType());
  }

  /**
   * Build log permissions.
   *
   * @return array
   *   Permissions array.
   */
  public function logPermissions(): array {
    return $this->buildPermissions($this->entityTypeManager->getStorage('log')->getEntityType());
  }

  /**
   * Build quantity permissions.
   *
   * @return array
   *   Permissions array.
   */
  public function quantityPermissions(): array {
    return $this->buildPermissions($this->entityTypeManager->getStorage('quantity')->getEntityType());
  }

  /**
   * Build research permissions for an entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return array
   *   Permissions array.
   */
  public function buildPermissions(EntityTypeInterface $entity_type): array {
    $bundle_permissions = $entity_type->getPermissionGranularity() == 'bundle';
    return $bundle_permissions ? $this->buildBundlePermissions($entity_type) : $this->buildEntityTypePermissions($entity_type);
  }

  /**
   * Build research permissions for a given entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return array
   *   Permissions array.
   */
  public function buildEntityTypePermissions(EntityTypeInterface $entity_type): array {
    $permissions = [];

    $entity_type_id = $entity_type->id();
    $plural_label = $entity_type->getPluralLabel();
    $permissions["view research_assigned $entity_type_id"] = [
      'title' => $this->t('View research-assigned @type', [
        '@type' => $plural_label,
      ]),
    ];
    $permissions["update research_assigned $entity_type_id"] = [
      'title' => $this->t('Update research-assigned @type', [
        '@type' => $plural_label,
      ]),
    ];
    return $permissions;
  }

  /**
   * Build research permissions for bundles of a given entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return array
   *   Permissions array.
   */
  public function buildBundlePermissions(EntityTypeInterface $entity_type): array {
    $permissions = [];

    $entity_type_id = $entity_type->id();
    $plural_label = $entity_type->getPluralLabel();
    $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
    foreach ($bundles as $bundle_id => $bundle_info) {
      $permissions["view research_assigned $bundle_id $entity_type_id"] = [
        'title' => $this->t(
          'View research-assigned @bundle @entity_type',
          [
            '@bundle' => $bundle_info['label'],
            '@entity_type' => $plural_label,
          ],
        ),
      ];
      $permissions["update research_assigned $bundle_id $entity_type_id"] = [
        'title' => $this->t(
          'Update research-assigned @bundle @entity_type',
          [
            '@bundle' => $bundle_info['label'],
            '@entity_type' => $plural_label,
          ],
        ),
      ];
    }
    return $permissions;
  }

}
