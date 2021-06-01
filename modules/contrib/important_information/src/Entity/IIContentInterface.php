<?php

namespace Drupal\important_information\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining II Content entities.
 *
 * @ingroup important_information
 */
interface IIContentInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the II Content name.
   *
   * @return string
   *   Name of the II Content.
   */
  public function getName();

  /**
   * Sets the II Content name.
   *
   * @param string $name
   *   The II Content name.
   *
   * @return \Drupal\important_information\Entity\IIContentInterface
   *   The called II Content entity.
   */
  public function setName($name);

  /**
   * Gets the II Content creation timestamp.
   *
   * @return int
   *   Creation timestamp of the II Content.
   */
  public function getCreatedTime();

  /**
   * Sets the II Content creation timestamp.
   *
   * @param int $timestamp
   *   The II Content creation timestamp.
   *
   * @return \Drupal\important_information\Entity\IIContentInterface
   *   The called II Content entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the II Content revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the II Content revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\important_information\Entity\IIContentInterface
   *   The called II Content entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the II Content revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the II Content revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\important_information\Entity\IIContentInterface
   *   The called II Content entity.
   */
  public function setRevisionUserId($uid);

}
