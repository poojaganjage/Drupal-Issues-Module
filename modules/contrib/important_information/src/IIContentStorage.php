<?php

namespace Drupal\important_information;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
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
class IIContentStorage extends SqlContentEntityStorage implements IIContentStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(IIContentInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {ii_content_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {ii_content_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(IIContentInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {ii_content_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('ii_content_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
