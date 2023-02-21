<?php

namespace Drupal\farm_rothamsted_experiment_research\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for research program entities.
 */
interface RothamstedProgramInterface extends ContentEntityInterface, EntityChangedInterface, RevisionLogInterface, EntityOwnerInterface {

  /**
   * Gets the program name.
   *
   * @return string
   *   The program name.
   */
  public function getName();

  /**
   * Sets the program name.
   *
   * @param string $name
   *   The program name.
   *
   * @return \Drupal\farm_rothamsted_experiment_research\Entity\RothamstedProgramInterface
   *   The program entity.
   */
  public function setName($name);

  /**
   * Gets the program creation timestamp.
   *
   * @return int
   *   Creation timestamp of the program.
   */
  public function getCreatedTime();

  /**
   * Sets the program creation timestamp.
   *
   * @param int $timestamp
   *   Creation timestamp of the program.
   *
   * @return \Drupal\farm_rothamsted_experiment_research\Entity\RothamstedProgramInterface
   *   The program entity.
   */
  public function setCreatedTime($timestamp);

}
