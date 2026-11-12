<?php

declare(strict_types=1);

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
   * Gets the proposal name.
   *
   * @return string
   *   The proposal name.
   */
  public function getName();

  /**
   * Sets the proposal name.
   *
   * @param string $name
   *   The proposal name.
   *
   * @return \Drupal\farm_rothamsted_experiment_research\Entity\RothamstedProposalInterface
   *   The proposal entity.
   */
  public function setName($name);

  /**
   * Gets the proposal creation timestamp.
   *
   * @return int
   *   Creation timestamp of the proposal.
   */
  public function getCreatedTime();

  /**
   * Sets the proposal creation timestamp.
   *
   * @param int $timestamp
   *   Creation timestamp of the proposal.
   *
   * @return \Drupal\farm_rothamsted_experiment_research\Entity\RothamstedProposalInterface
   *   The proposal entity.
   */
  public function setCreatedTime($timestamp);

}
