<?php

namespace Drupal\important_information\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for II Content entities.
 */
class IIContentViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    return $data;
  }

}
