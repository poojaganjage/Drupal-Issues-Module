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

namespace Drupal\flood_unblock\Commands;

use Drush\Commands\DrushCommands;
use Drupal\flood_unblock\FloodUnblockManager;

/**
 * Returns responses for flood_unblock module.
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
class FloodUnblockCommands extends DrushCommands {

  /**
   * The FloodUnblockManager information.
   *
   * @var \Drupal\flood_unblock\FloodUnblockManager
   */
  private $manager;

  /**
   * FloodUnblockCommands constructor.
   *
   * @param $manager The FloodUnblockManager constructor.
   */
  public function __construct(FloodUnblockManager $manager) {
    $this->manager = $manager;
  }

  /**
   * Clears the floods based on IP.
   *
   * @param string $ip IP to clear.
   *
   * @command flood_unblock:ip
   * @usage   flood_unblock:ip
   */
  public function unblockIp($ip = null) {
    $this->manager->flood_unblock_clear_event('user.failed_login_ip', $ip);
    $this->output()->writeln('Done');
  }

  /**
   * Clears the floods based on user.
   *
   * @param string $user User to clear.
   *
   * @command flood_unblock:user
   * @usage   flood_unblock:user
   */
  public function unblockUser($user = null) {
    $this->manager->flood_unblock_clear_event('user.failed_login_user', $user);
    $this->output()->writeln('Done');
  }

  /**
   * Clears all floods in the system.
   *
   * @command flood_unblock:all
   * @usage   flood_unblock:all
   */
  public function unblockAll() {
    $this->manager->flood_unblock_clear_event('user.failed_login_ip', null);
    $this->manager->flood_unblock_clear_event('user.failed_login_user', null);
    $this->manager->flood_unblock_clear_event('user.http_login', null);
    $this->output()->writeln('Done');
  }

}
