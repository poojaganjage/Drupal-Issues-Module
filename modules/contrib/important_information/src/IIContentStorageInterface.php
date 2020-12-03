<?php

namespace Drupal\important_information;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\important_information\Entity\IIContentInterface;

/**
 * Defines the storage handler class for II Content entities.
 *
 * This extends the base storage class, adding required special handling for
 * II Content entities.
 *
 * @ingroup important_information
 */
interface IIContentStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of II Content revision IDs for a specific II Content.
   *
   * @param \Drupal\important_information\Entity\IIContentInterface $entity
   *   The II Content entity.
   *
   * @return int[]
   *   II Content revision IDs (in ascending order).
   */
  public function revisionIds(IIContentInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as II Content author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   II Content revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\important_information\Entity\IIContentInterface $entity
   *   The II Content entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(IIContentInterface $entity);

  /**
   * Unsets the language for all II Content with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
