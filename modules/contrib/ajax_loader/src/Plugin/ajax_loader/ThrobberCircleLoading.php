<?php

namespace Drupal\ajax_loader\Plugin\ajax_loader;

use Drupal\ajax_loader\ThrobberPluginBase;

/**
 * Class ThrobberChasingDots.
 *
 * @Throbber(
 *   id = "throbber_circle_loading",
 *   label = @Translation("Circle Loading")
 * )
 */
class ThrobberCircleLoading extends ThrobberPluginBase {

  /**
   * Function to set markup.
   *
   * @inheritdoc
   */
  protected function setMarkup() {
    return '<div class="ajax-throbber sk-loading-circle">
            <div class="sk-loading-circle1 sk-circle-loading"></div>
            </div>';
  }

  /**
   * Function to set css file.
   *
   * @inheritdoc
   */
  protected function setCssFile() {
    return $this->path . '/css/circle-loading.css';
  }

}