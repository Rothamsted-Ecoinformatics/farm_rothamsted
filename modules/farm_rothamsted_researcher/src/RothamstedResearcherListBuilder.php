<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_researcher;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * List builder for researchers.
 */
class RothamstedResearcherListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = new TranslatableMarkup('Name');
    $header['organization'] = new TranslatableMarkup('Organisation');
    $header['department'] = new TranslatableMarkup('Department');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\asset\Entity\AssetInterface $entity */
    $row['name'] = $entity->toLink($entity->label(), 'canonical')->toString();
    $row['organization'] = $entity->get('organization')->value;
    $row['department'] = $entity->get('department')->value;
    return $row + parent::buildRow($entity);
  }

}
