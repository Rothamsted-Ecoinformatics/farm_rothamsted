<?php

namespace Drupal\farm_rothamsted_researcher;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for researchers.
 */
class RothamstedResearcherListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    $header['role'] = $this->t('Role');
    $header['organization'] = $this->t('Organisation');
    $header['department'] = $this->t('Department');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\asset\Entity\AssetInterface $entity */
    $row['name'] = $entity->toLink($entity->label(), 'canonical')->toString();
    $row['role'] = $entity->get('role')->value;
    $row['organization'] = $entity->get('organization')->value;
    $row['department'] = $entity->get('department')->value;
    return $row + parent::buildRow($entity);
  }

}
