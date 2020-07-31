<?php

namespace Drupal\commerce_fraud\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for Commerce Fraud Help page.
 */
class ContentController extends ControllerBase {
  // Todo To make this content more descriptive.

  /**
   * {@inheritdoc}
   */
  public function content() {
    return ['#type' => 'markup', '#markup' => t('Detects potentially fraudulent orders')];
  }

}
