<?php

namespace Drupal\farm_rothamsted_experiment_research;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Default list builder for rothamsted entities.
 */
class RothamstedEntityListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['name'] = $entity->toLink($entity->label(), 'canonical')->toString();
    return $row + parent::buildRow($entity);
  }

}
