<?php

/**
 * Provides autologout manager Implementation.
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

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManager;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\user\UserData;
use Drupal\Component\Utility\Xss;
use Drupal\user\Entity\User;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides autologout manager Implementation.
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
class AutologoutManager implements AutologoutManagerInterface
{

    use StringTranslationTrait;

    /**
     * The module manager service.
     *
     * @var \Drupal\Core\Extension\ModuleHandlerInterface
     */
    protected $moduleHandler;

    /**
     * The config object for 'autologout.settings'.
     *
     * @var \Drupal\Core\Config\Config
     */
    protected $autoLogoutSettings;

    /**
     * The config factory service.
     *
     * @var \Drupal\Core\Config\ConfigFactoryInterface
     */
    protected $configFactory;

    /**
     * The Messenger service.
     *
     * @var \Drupal\Core\Messenger\MessengerInterface
     */
    protected $messenger;
    /**
     * The current user.
     *
     * @var \Drupal\Core\Session\AccountInterface
     */
    protected $currentUser;

    /**
     * Logger service.
     *
     * @var \Drupal\Core\Logger\LoggerChannelInterface
     */
    protected $logger;

    /**
     * The session.
     *
     * @var \Drupal\Core\Session\SessionManager
     */
    protected $session;

    /**
     * Data of the user.
     *
     * @var \Drupal\user\UserDataInterface
     */
    protected $userData;

    /**
     * The Time Service.
     *
     * @var \Drupal\Component\Datetime\TimeInterface
     */
    protected $time;

    /**
     * The Entity Type Manager.
     *
     * @var Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * Constructs an AutologoutManager object.
     *
     * @param $module_handler    The module handler
     * @param $config_factory    The config factory.
     * @param $messenger         The messenger service.
     * @param $current_user      Data of the user.
     * @param $logger            Logger service.
     * @param $sessionManager    The session.
     * @param $userData          Data of the user.
     * @param $time              The time service.
     * @param $entityTypeManager The Entity Type Manager.
     */
    public function __construct(
        ModuleHandlerInterface $module_handler,
        ConfigFactoryInterface $config_factory,
        MessengerInterface $messenger,
        AccountInterface $current_user,
        LoggerChannelFactoryInterface $logger,
        SessionManager $sessionManager,
        UserData $userData,
        TimeInterface $time,
        EntityTypeManagerInterface $entityTypeManager
    ) {
        $this->moduleHandler = $module_handler;
        $this->autoLogoutSettings = $config_factory->get('autologout.settings');
        $this->configFactory = $config_factory;
        $this->messenger = $messenger;
        $this->currentUser = $current_user;
        $this->logger = $logger->get('autologout');
        $this->session = $sessionManager;
        $this->userData = $userData;
        $this->time = $time;
        $this->entityTypeManager = $entityTypeManager;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function preventJs()
    {
        foreach ($this->moduleHandler->invokeAll('autologout_prevent') as $prevent) {
            if (!empty($prevent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function refreshOnly()
    {
        foreach ($this->moduleHandler->invokeAll('autologout_refresh_only') 
        as $module_refresh_only) {
            if (!empty($module_refresh_only)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @return object
     */
    public function inactivityMessage()
    {
        $message = Xss::filter($this->autoLogoutSettings->get('inactivity_message'));
        $type = $this->autoLogoutSettings->get('inactivity_message_type');
        if (!empty($message)) {
            $this->messenger->addMessage($this->t($message), $type);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return object
     */
    public function logout()
    {
        $user = $this->currentUser;
        if ($this->autoLogoutSettings->get('use_watchdog')) {
            $this->logger->info(
                'Session automatically closed for %name by autologout.',
                ['%name' => $user->getAccountName()]
            );
        }

        // Destroy the current session.
        $this->moduleHandler->invokeAll('user_logout', [$user]);
        $this->session->destroy();
        $user->setAccount(new AnonymousUserSession());
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRoleTimeout()
    {
        $roles = user_roles(true);
        $role_timeout = [];

        // Go through roles, get timeouts for each and return as array.
        foreach ($roles as $name => $role) {
            $role_settings = $this->configFactory->get('autologout.role.' . $name);
            if ($role_settings->get('enabled')) {
                $timeout_role = $role_settings->get('timeout');
                $role_timeout[$name] = $timeout_role;
            }
        }

        return $role_timeout;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRoleUrl()
    {
        $roles = user_roles(true);
        $role_url = [];

        // Go through roles, get timeouts for each and return as array.
        foreach ($roles as $name => $role) {
            $role_settings = $this->configFactory->get('autologout.role.' . $name);
            if ($role_settings->get('enabled')) {
                $url_role = $role_settings->get('url');
                $role_url[$name] = $url_role;
            }
        }
        return $role_url;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRemainingTime()
    {
        $timeout = $this->getUserTimeout();
        $time_passed = isset($_SESSION['autologout_last'])
        ? $this->time->getRequestTime() - $_SESSION['autologout_last']
        : 0;

        return $timeout - $time_passed;
    }

    /**
     * {@inheritdoc}
     *
     * @return object
     */
    public function createTimer()
    {
        return $this->getRemainingTime();
    }

    /**
     * {@inheritdoc}
     *
     * @param $uid This is the unique id.
     *
     * @return int
     */
    public function getUserTimeout($uid = null)
    {
        if (is_null($uid)) {
            // If $uid is not provided, use the logged in user.
            $user = $this->currentUser;
        } else {
            $user = $this->entityTypeManager->getStorage('user')->load($uid);
        }

        if ($user->id() == 0) {
            // Anonymous doesn't get logged out.
            return 0;
        }
        $user_timeout = $this->userData->get('autologout', $user->id(), 'timeout');

        if (is_numeric($user_timeout)) {
            // User timeout takes precedence.
            return $user_timeout;
        }

        // Get role timeouts for user.
        if ($this->autoLogoutSettings->get('role_logout')) {
            $user_roles = $user->getRoles();
            $output = [];
            $timeouts = $this->getRoleTimeout();
            foreach ($user_roles as $rid => $role) {
                if (isset($timeouts[$role])) {
                    $output[$rid] = $timeouts[$role];
                }
            }

            // Assign the lowest/highest timeout value to be session timeout value.
            if (!empty($output)) {
                // If one of the user's roles has a unique timeout, use this.
                if ($this->autoLogoutSettings->get('role_logout_max')) {
                    return max($output);
                } else {
                    return min($output);
                }
            }
        }

        // If no user or role override exists, return the default timeout.
        return $this->autoLogoutSettings->get('timeout');
    }

    /**
     * {@inheritdoc}
     *
     * @param $uid This is the unique id.
     *
     * @return int
     */
    public function getUserRedirectUrl($uid = null)
    {
        if (is_null($uid)) {
            // If $uid is not provided, use the logged in user.
            $user = $this->entityTypeManager->getStorage('user')
                ->load($this->currentUser->id());
        } else {
            // $user = User::load($uid);
            $user = $this->entityTypeManager->getStorage('user')->load($uid);
        }

        if ($user->id() == 0) {
            // Anonymous doesn't get logged out.
            return;
        }

        // Get role timeouts for user.
        if ($this->autoLogoutSettings->get('role_logout')) {
            $user_roles = $user->getRoles();
            $output = [];
            $urls = $this->getRoleUrl();
            foreach ($user_roles as $rid => $role) {
                if (isset($urls[$role])) {
                    $output[$rid] = $urls[$role];
                }
            }

            // Assign the first matching Role.
            if (!empty($output) && !empty(reset($output))) {
                // If one of the user's roles has a unique URL, use this.
                return reset($output);
            }
        }

        // If no user or role override exists, return the default timeout.
        return $this->autoLogoutSettings->get('redirect_url');
    }

    /**
     * {@inheritdoc}
     *
     * @param $user This is the user logout role.
     *
     * @return bool
     */
    public function logoutRole(User $user)
    {
        if ($this->autoLogoutSettings->get('role_logout')) {
            foreach ($user->roles as $name => $role) {
                if ($this->configFactory->get(
                    'autologout.role.' . $name . 
                    '.enabled'
                )
                ) {
                    return true;
                }
            }
        }

        return false;
    }

}
