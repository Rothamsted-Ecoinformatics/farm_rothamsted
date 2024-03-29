<?php

namespace Drupal\farm_rothamsted_researcher\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for researcher entities.
 */
interface RothamstedResearcherInterface extends ContentEntityInterface, EntityChangedInterface, RevisionLogInterface, EntityOwnerInterface {

  /**
   * Gets the researcher name.
   *
   * @return string
   *   The researcher name.
   */
  public function getName();

  /**
   * Sets the researcher name.
   *
   * @param string $name
   *   The researcher name.
   *
   * @return \Drupal\farm_rothamsted_researcher\Entity\RothamstedResearcherInterface
   *   The researcher entity.
   */
  public function setName($name);

  /**
   * Gets the researcher creation timestamp.
   *
   * @return int
   *   Creation timestamp of the researcher.
   */
  public function getCreatedTime();

  /**
   * Sets the researcher creation timestamp.
   *
   * @param int $timestamp
   *   Creation timestamp of the researcher.
   *
   * @return \Drupal\farm_rothamsted_researcher\Entity\RothamstedResearcherInterface
   *   The researcher entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Helper function to get the researcher's notification email.
   *
   * @param bool $force
   *   A boolean to enforce returning an email despite user preferences.
   * @param string|null $notification_type
   *   An optional notification type.
   *
   * @return string|null
   *   Returns NULL if the farm user account has email disabled.
   */
  public function getNotificationEmail(bool $force = FALSE, string $notification_type = NULL): ?string;

}
