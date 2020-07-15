<?php

/**
 * Provides flood_unblock Implementation.
 *
 * @category Module
 *
 * @package Contrib
 *
 * @author Display Name <username@example.com>
 *
 * @license https://www.drupal.org Drupal 8
 *
 * @version "GIT: <1001>"
 *
 * @link https://www.drupal.org
 */

namespace Drupal\flood_unblock;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Flood Unblock Manager Class Implementation.
 *
 * @category Module
 *
 * @package Contrib
 *
 * @author Display Name <username@example.com>
 *
 * @license https://www.drupal.org Drupal 8
 *
 * @version "Release: 8"
 *
 * @link https://www.drupal.org
 */
class FloodUnblockManager {

  use StringTranslationTrait;

  /**
   * The Database Connection
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The Entity Type Manager Interface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Flood Interface.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $flood;

  /**
   * The Immutable Config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * FloodUnblockAdminForm constructor.
   *
   * @param $database          The Database Connection.
   * @param $flood             The Flood Interface.
   * @param $configFactory     The Config Factory Interface.
   * @param $entityTypeManager The Entity Type Manager Interface.
   * @param $messenger         The Messenger Interface.
   */
  public function __construct(Connection $database, FloodInterface $flood, 
    ConfigFactoryInterface $configFactory, EntityTypeManagerInterface 
    $entityTypeManager, MessengerInterface $messenger) {
    $this->database = $database;
    $this->flood = $flood;
    $this->entityTypeManager = $entityTypeManager;
    $this->config = $configFactory->get('user.flood');
    $this->messenger = $messenger;
  }

  /**
   * Generate rows from the entries in the flood table.
   *
   * @return array
   *   Ip blocked entries in the flood table.
   */
  public function getBlockedIpEntries() {
    $entries = [];

    if ($this->database->schema()->tableExists('flood')) {
        $query = $this->database->select('flood', 'f');
        $query->addField('f', 'identifier');
        $query->addField('f', 'identifier', 'ip');
        $query->addExpression('count(*)', 'count');
        $query->condition('f.event', '%failed_login_ip', 'LIKE');
        $query->groupBy('identifier');
        $results = $query->execute();

        foreach ($results as $result) {
          if (function_exists('smart_ip_get_location')) {
              $location = smart_ip_get_location($result->ip);
              $location_string = sprintf(
                " (%s %s %s)", 
                $location['city'], $location['region'], 
                $location['country_code']
              );
          } else {
              $location_string = '';
          }

          $blocked = !$this->flood->isAllowed(
            'user.failed_login_ip', 
            $this->config->get('ip_limit'), $this->config->get('ip_window'), 
            $result->ip
          );

          $entries[$result->identifier] = [
          'type' => 'ip',
          'ip' => $result->ip,
          'count' => $result->count,
          'location' => $location_string,
          'blocked' => $blocked,
          ];
        }
    }

    return $entries;
  }

  /**
   * Generate rows from the entries in the flood table.
   *
   * @return array
   *   User blocked entries in the flood table.
   */
  public function getBlockeduUserEntries() {
    $entries = [];

    if ($this->database->schema()->tableExists('flood')) {
        $query = $this->database->select('flood', 'f');
        $query->addField('f', 'identifier');
        $query->addExpression('count(*)', 'count');
        $query->condition('f.event', '%failed_login_user', 'LIKE');
        $query->groupBy('identifier');
        $results = $query->execute();

        foreach ($results as $result) {
          $parts = explode('-', $result->identifier);
          $result->uid = $parts[0];
          $result->ip = $parts[1] ?? null;
          if (function_exists('smart_ip_get_location') && $result->ip) {
              $location = smart_ip_get_location($result->ip);
              $location_string = sprintf(
                " (%s %s %s)", $location['city'],
                $location['region'], $location['country_code']
              );
          } else {
              $location_string = '';
          }

          $blocked = !$this->flood->isAllowed(
            'user.failed_login_user', 
            $this->config->get('user_limit'), $this->config->get(
                'user_window'
            ), 
            $result->identifier
          );

          /**
           * To load user id.
           *
           * @var \Drupal\user\Entity\User $user 
           */
          $user = $this->entityTypeManager->getStorage('user')
          ->load($result->uid);

          if (isset($user)) {
              $user_link = $user->toLink($user->getAccountName());
          } else {
              $user_link = $this->t(
                'Deleted user: @user', 
                ['@user' => $result->uid]
              );
          }

          $entries[$result->identifier] = [
          'type' => 'user',
          'uid' => $result->uid,
          'ip' => $result->ip,
          'username' => $user_link,
          'count' => $result->count,
          'location' => $location_string,
          'blocked' => $blocked,
          ];
        }
    }

    return $entries;
  }

  /**
   * The function that clear the flood.
   *
   * @param $type       The type variable.
   * @param $identifier The identifier variable.
   *
   * @return string
   */
  public function floodUnblockClearEvent($type, $identifier) {
    $txn = $this->database->startTransaction('flood_unblock_clear');
    try {
        $query = $this->database->delete('flood')
        ->condition('event', '%' . $type, 'LIKE');
        if (isset($identifier)) {
            $query->condition('identifier', $identifier);
        }
        $success = $query->execute();
        if ($success) {
            \Drupal::messenger()->addMessage(
              $this->t('Flood entries cleared.'),
              'status', false
            );
        }
    } catch (\Exception $e) {
        // Something went wrong somewhere, so roll back now.
        $txn->rollback();
        // Log the exception to watchdog.
        watchdog_exception('type', $e);
        \Drupal::messenger()->addMessage(
            $this->t(
              'Error: @error', 
              ['@error' => (string) $e]
          ), 'error'
        );
      }
  }
  
}
