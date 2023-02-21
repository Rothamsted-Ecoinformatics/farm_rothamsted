<?php

namespace Drupal\farm_rothamsted_experiment_research\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for research design entities.
 */
interface RothamstedDesignInterface extends ContentEntityInterface, EntityChangedInterface, RevisionLogInterface, EntityOwnerInterface {

  /**
   * Gets the design name.
   *
   * @return string
   *   The design name.
   */
  public function getName();

  /**
   * Sets the design name.
   *
   * @param string $name
   *   The design name.
   *
   * @return \Drupal\farm_rothamsted_experiment_research\Entity\RothamstedDesignInterface
   *   The design entity.
   */
  public function setName($name);

  /**
   * Gets the design creation timestamp.
   *
   * @return int
   *   Creation timestamp of the design.
   */
  public function getCreatedTime();

  /**
   * Sets the design creation timestamp.
   *
   * @param int $timestamp
   *   Creation timestamp of the design.
   *
   * @return \Drupal\farm_rothamsted_experiment_research\Entity\RothamstedDesignInterface
   *   The design entity.
   */
  public function setCreatedTime($timestamp);

}
