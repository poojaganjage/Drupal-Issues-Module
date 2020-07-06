<?php

/**
 * Provides autologout manager interface Implementation.
 *
 * @category Module
 *
 * @package Contrib
 *
 * @author Display Name <username@example.com>
 *
 * @license www.google.com ABC
 *
 * @version "GIT: <1001>"
 *
 * @link www.google.com
 */

namespace Drupal\autologout;

use Drupal\user\Entity\User;

/**
 * Provides autologout manager interface Implementation.
 *
 * @category Module
 *
 * @package Contrib
 *
 * @author Display Name <username@example.com>
 *
 * @license www.google.com ABC
 *
 * @version "Release: 8"
 *
 * @link www.google.com
 */
interface AutologoutManagerInterface
{

    /**
     * Get the timer HTML markup.
     *
     * @return string
     *   HTML to insert a countdown timer.
     */
    public function createTimer();

    /**
     * Get the time remaining before logout.
     *
     * @return int
     *   Number of seconds remaining.
     */
    public function getRemainingTime();

    /**
     * Go through every role to get timeout value, default is the global timeout.
     *
     * @return int
     *   Number of seconds timeout set for the user role.
     */
    public function getRoleTimeout();

    /**
     * Iterate roles to get the per-role logout URL, default is the global URL.
     *
     * @return array
     *   List of roles with logout URL.
     */
    public function getRoleUrl();

    /**
     * Get a user's timeout in seconds.
     *
     * @param int $uid (Optional) Provide a user's uid to get the timeout for.
     *                 Default is the logged in user.
     *
     * @return int
     *   The number of seconds the user can be idle for before being logged out.
     *   A value of 0 means no timeout.
     */
    public function getUserTimeout($uid = null);

    /**
     * Get a user's logout URL.
     *
     * @param null|int $uid User id or NULL to use current logged in user.
     *
     * @return null|string
     *   User's logout URL or NULL for anonymous user.
     */
    public function getUserRedirectUrl($uid = null);

    /**
     * Perform Logout.
     *
     * Helper to perform the actual logout. Destroys the session of the logged
     * in user.
     *
     * @return bool
     */
    public function logout();

    /**
     * Helper to determine if a given user should be autologged out.
     *
     * @param \Drupal\user\Entity\User $user User entity.
     *
     * @return bool
     *   TRUE if the user should be autologged out, otherwise FALSE.
     */
    public function logoutRole(User $user);

    /**
     * Display the inactivity message if required when the user is logged out.
     *
     * @return bool
     */
    public function inactivityMessage();

    /**
     * Determine if autologout should be prevented.
     *
     * @return bool
     *   TRUE if there is a reason not to autologout
     *   the current user on the current page.
     */
    public function preventJs();

    /**
     * Determine if connection should be refreshed.
     *
     * @return bool
     *   TRUE if something about the current context should keep the connection
     *   open. FALSE and the standard countdown to autologout applies.
     */
    public function refreshOnly();

}
