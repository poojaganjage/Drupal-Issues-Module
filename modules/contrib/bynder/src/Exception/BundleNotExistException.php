<?php

namespace Drupal\bynder\Exception;

use Drupal\Core\Url;

/**
 * Exception indicating that the selected bundle does not exist.
 */
class BundleNotExistException extends BynderException {

  /**
   * {@inheritdoc}
   */
  protected $adminPermission = 'administer entity browsers';

  /**
   * Constructs BundleNotExistException.
   *
   * @param string $bundle
   *   Name of the bundle.
   */
  public function __construct($bundle) {
    $log_message = 'Media type @bundle does not exist. Please fix the Bynder <a href=":eb_conf">search widget configuration</a>.';
    $log_message_args = [
      ':eb_conf' => Url::fromRoute('entity.entity_browser.collection')
        ->toString(),
      '@bundle' => $bundle,
    ];
    $admin_message = $this->t($log_message, $log_message_args);
    $message = $this->t(
      'Bynder integration is not configured correctly. Please contact the site administrator.'
    );
    parent::__construct(
      $message,
      $admin_message,
      $log_message,
      $log_message_args
    );
  }

}
