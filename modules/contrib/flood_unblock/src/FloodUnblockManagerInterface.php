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

/**
 * Flood Unblock Manager Interface Class Implementation.
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
interface FloodUnblockManagerInterface {

    /**
     * Generate rows from the entries in the flood table.
     *
     * @return array
     * Ip blocked entries in the flood table.
     */
    public function getBlockedIpEntries();

    /**
     * Generate rows from the entries in the flood table.
     *
     * @return array
     *   User blocked entries in the flood table.
     */
    public function getBlockedUserEntries();

    /**
     * The function that clear the flood.
     *
     * @param $type       The type variable.
     * @param $identifier The identifier variable.
     *
     * @return string
     */
    public function floodUnblockClearEvent($type, $identifier);

}
