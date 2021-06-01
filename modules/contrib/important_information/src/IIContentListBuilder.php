<?php

namespace Drupal\important_information;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of II Content entities.
 *
 * @ingroup important_information
 */
class IIContentListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('II Content ID');
    $header['name'] = $this->t('Name');
    $header['type'] = $this->t('Content Type');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\important_information\Entity\IIContent $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.ii_content.edit_form',
      ['ii_content' => $entity->id()]
    );
    $row['type'] = $entity->bundle();
    $row['status'] = $entity->isPublished() ? $this->t('published') : $this->t('not published');
    return $row + parent::buildRow($entity);
  }

}
