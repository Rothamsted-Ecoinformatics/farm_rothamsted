<?php

namespace Drupal\farm_rothamsted_experiment_research\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for research proposal entities.
 */
interface RothamstedProposalInterface extends ContentEntityInterface, EntityChangedInterface, RevisionLogInterface, EntityOwnerInterface {

  /**
   * Gets the experiment name.
   *
   * @return string
   *   The experiment name.
   */
  public function getName();

  /**
   * Sets the experiment name.
   *
   * @param string $name
   *   The experiment name.
   *
   * @return \Drupal\farm_rothamsted_experiment_research\Entity\RothamstedExperimentInterface
   *   The experiment entity.
   */
  public function setName($name);

  /**
   * Gets the experiment creation timestamp.
   *
   * @return int
   *   Creation timestamp of the experiment.
   */
  public function getCreatedTime();

  /**
   * Sets the experiment creation timestamp.
   *
   * @param int $timestamp
   *   Creation timestamp of the experiment.
   *
   * @return \Drupal\farm_rothamsted_experiment_research\Entity\RothamstedExperimentInterface
   *   The experiment entity.
   */
  public function setCreatedTime($timestamp);

}
