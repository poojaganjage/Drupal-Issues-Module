<?php

namespace Drupal\simple_live_css\Utility;

/**
 * Provides utility function for the live_css module.
 */
class InjectCssFileUtility {

  const FILE_PATH = 'public://simple-live-css-inject.css';

  /**
   * Return the relative inject css path.
   *
   * @return string
   *   A relative url.
   */
  public static function getRelativePath() {
    return file_url_transform_relative(file_create_url(static::FILE_PATH));
  }

}
