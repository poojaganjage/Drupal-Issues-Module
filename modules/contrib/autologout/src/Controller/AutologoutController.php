<?php

/**
 * Provides autologout Implementation.
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

namespace Drupal\autologout\Controller;

use Drupal\autologout\AutologoutManagerInterface;
use Drupal\Component\Datetime\TimeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\Core\Controller\ControllerBase;
//use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Routing\UrlGenerator;

/**
 * Returns responses for autologout module routes.
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
class AutologoutController extends ControllerBase
{

    /**
     * The autologout manager service.
     *
     * @var \Drupal\autologout\AutologoutManagerInterface
     */
    protected $autoLogoutManager;


    /**
     * The Time Service.
     *
     * @var \Drupal\Component\Datetime\TimeInterface
     */
    protected $time;

    /**
     * The Url Generate Service.
     *
     * @var \Drupal\Core\Routing\UrlGenerator
     */
    protected $urlGenerator;

    /**
     * Constructs an AutologoutSubscriber object.
     *
     * The autologout manager service.
     *
     * @param $autologout   The autologout service.
     * @param $time         The time service.
     * @param $urlGenerator The Url Generate Service
     */
    public function __construct(
        AutologoutManagerInterface $autologout,
        TimeInterface $time, UrlGenerator $urlGenerator
    ) {
        $this->autoLogoutManager = $autologout;
        $this->time = $time;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Create Method.
     *
     * @param $container The container variable.
     *
     * @return object
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('autologout.manager'),
            $container->get('datetime.time'),
            $container->get('url_generator.non_bubbling')
        );
    }

    /**
     * Alternative logout.
     *
     * @return string
     */
    public function altLogout()
    {
        $this->autoLogoutManager->logout();
        $redirect_url = $this->config('autologout.settings')->get('redirect_url');
        $url = $this->urlGenerator->generateFromRoute(
            $redirect_url,
            [
            'absolute' => true,
            'query' => [
            'autologout_timeout' => 1,
            ],
            ]
        );

        return new RedirectResponse($url->toString());
    }

    /**
     * AJAX logout.
     *
     * @return Time
     */
    public function ajaxLogout()
    {
        $this->autoLogoutManager->logout();
        $response = new AjaxResponse();
        $response->setStatusCode(200);

        return $response;
    }

    /**
     * Ajax callback to reset the last access session variable.
     *
     * @return Time
     */
    public function ajaxSetLast()
    {
        $_SESSION['autologout_last'] = $this->time->getRequestTime();

        // Reset the timer.
        $response = new AjaxResponse();
        $markup = $this->autoLogoutManager->createTimer();
        $response->addCommand(new ReplaceCommand('#timer', $markup));

        return $response;
    }

    /**
     * AJAX callback that returns the time remaining for this user is logged out.
     *
     * @return Time
     */
    public function ajaxGetRemainingTime()
    {
        $time_remaining_ms = $this->autoLogoutManager->getRemainingTime() * 1000;

        // Reset the timer.
        $response = new AjaxResponse();
        $markup = $this->autoLogoutManager->createTimer();

        $response->addCommand(new ReplaceCommand('#timer', $markup));
        $response->addCommand(new SettingsCommand(['time' => $time_remaining_ms]));

        return $response;
    }

}
