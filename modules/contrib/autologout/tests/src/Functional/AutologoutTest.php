<?php

/**
 * Describe autologout test file.
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

namespace Drupal\Tests\autologout\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Tests the autologout's features.
 *
 * @description Ensures that the autologout module functions as expected
 *
 * @group Autologout
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
class AutologoutTest extends BrowserTestBase
{
    use StringTranslationTrait;
    /**
     * Modules to enable.
     *
     * @var array
     */
    public static $modules = [
    'node',
    'system',
    'user',
    'views',
    'autologout',
    'block',
    ];

    /**
     * User with admin rights.
     *
     * @var \Drupal\user\Entity\User
     */
    protected $privilegedUser;

    /**
     * The config factory service.
     *
     * @var \Drupal\Core\Config\ConfigFactoryInterface
     */
    protected $configFactory;

    /**
     * Stores the user data service used by the test.
     *
     * @var \Drupal\user\UserDataInterface
     */
    public $userData;

    /**
     * Performs any pre-requisite tasks that need to happen.
     * 
     * @return object
     */
    public function setUp()
    {
        parent::setUp();
        // Create and log in our privileged user.
        $this->privilegedUser = $this->drupalCreateUser(
            [
            'access content',
            'administer site configuration',
            'access site reports',
            'access administration pages',
            'bypass node access',
            'administer content types',
            'administer nodes',
            'administer autologout',
            'change own logout threshold',
            'access site reports',
            'view the administration theme',
            ]
        );

        $this->configFactory = $this->container->get('config.factory');
        $this->userData = $this->container->get('user.data');

        // For the purposes of the test, set the timeout periods to 10 seconds.
        $this->configFactory->getEditable('autologout.settings')
            ->set('timeout', 10)
            ->set('padding', 0)
            ->save();
        // Make node page default.
        $this->configFactory->getEditable('system.site')
            ->set('page.front', 'node')
            ->save();

        $this->drupalLogin($this->privilegedUser);
    }

    /**
     * Tests a user is logged out after the default timeout period.
     *
     * @return object
     */
    public function testAutologoutDefaultTimeout()
    {
        // Check that the user can access the page after login.
        $this->drupalGet('node');
        $this->assertSession()->statusCodeEquals(200);
        self::assertTrue($this->drupalUserIsLoggedIn($this->privilegedUser));

        // Wait for timeout period to elapse.
        sleep(15);

        // Check we are now logged out.
        $this->drupalGet('node');
        $this->assertSession()->statusCodeEquals(200);
        self::assertFalse($this->drupalUserIsLoggedIn($this->privilegedUser));
    }

    /**
     * Tests a user is logged out with the alternate logout method.
     *
     * @return object
     */
    public function testAutologoutAlternateLogoutMethod()
    {
        // Test that alternate logout works as expected.
        $this->drupalGet('autologout_alt_logout');
        $this->assertSession()->statusCodeEquals(200);
        $this->assertSession()->pageTextContains(
            $this->t('You have been logged out due to inactivity.')
        );

        // Check further logout requests result in access denied.
        $this->drupalGet('autologout_alt_logout');
        $this->assertSession()->statusCodeEquals(403);
    }

    /**
     * Tests a user is not logged out within the default timeout period.
     *
     * @return object
     */
    public function testAutologoutNoLogoutInsideTimeout()
    {
        // Check that the user can access the page after login.
        $this->drupalGet('node');
        $this->assertSession()->statusCodeEquals(200);
        self::assertTrue($this->drupalUserIsLoggedIn($this->privilegedUser));

        // Wait within the timeout period.
        sleep(5);

        // Check we are still logged in.
        $this->drupalGet('node');
        $this->assertSession()->statusCodeEquals(200);
        self::assertTrue($this->drupalUserIsLoggedIn($this->privilegedUser));
    }

    /**
     * Tests a user is logged out and denied access to admin pages.
     *
     * @return object
     */
    public function testAutologoutDefaultTimeoutAccessDeniedToAdmin()
    {
        $autologout_settings = $this->configFactory
            ->getEditable('autologout.settings');
        // Enforce auto logout of admin pages.
        $autologout_settings->set('enforce_admin', false)->save();

        // Check that the user can access the page after login.
        $this->drupalGet('admin/reports/status');
        $this->assertSession()->statusCodeEquals(200);
        self::assertTrue($this->drupalUserIsLoggedIn($this->privilegedUser));

        // Wait for timeout period to elapse.
        sleep(15);

        // Check we are now logged out.
        $this->drupalGet('admin/reports/status');
        $this->assertSession()->statusCodeEquals(403);
        self::assertFalse($this->drupalUserIsLoggedIn($this->privilegedUser));
        $this->assertSession()->pageTextContains(
            $this->t('You have been logged out due to inactivity.')
        );
    }

    /**
     * Tests integration with the remember me module.
     *
     * Users who checked remember_me on login should never be logged out.
     *
     * @return object
     */
    public function testNoAutologoutWithRememberMe()
    {
        // Set the remember_me module data bit to TRUE.
        $this->userData->set(
            'remember_me',
            $this->privilegedUser->id(),
            'remember_me',
            true
        );

        // Check that the user can access the page after login.
        $this->drupalGet('node');
        $this->assertSession()->statusCodeEquals(200);
        self::assertTrue($this->drupalUserIsLoggedIn($this->privilegedUser));

        // Wait for timeout period to elapse.
        sleep(15);

        // Check we are still logged in.
        $this->drupalGet('node');
        $this->assertSession()->statusCodeEquals(200);
        self::assertTrue($this->drupalUserIsLoggedIn($this->privilegedUser));
    }

    /**
     * Tests the behaviour of custom message displayed on autologout.
     *
     * @return object
     */
    public function testCustomMessage()
    {
        $autologout_settings = $this->configFactory
            ->getEditable('autologout.settings');
        $inactivity_message = 'Custom message for test';

        // Update message string in configuration.
        $autologout_settings->set('inactivity_message', $inactivity_message)
            ->save();

        // Set time out for 5 seconds.
        $autologout_settings->set('timeout', 5)->save();

        // Wait for 20 seconds for timeout.
        sleep(20);

        // Access the admin page and verify user is logged out and custom message
        // is displayed.
        $this->drupalGet('admin/reports/status');
        self::assertFalse($this->drupalUserIsLoggedIn($this->privilegedUser));
        $this->assertSession()->pageTextContains($inactivity_message);
    }

    /**
     * Tests the behaviour of application when Autologout is enabled for admin.
     *
     * @return object
     */
    public function testAutologoutAdminPages()
    {
        $autologout_settings = $this->configFactory
            ->getEditable('autologout.settings');
        // Enforce auto logout of admin pages.
        $autologout_settings->set('enforce_admin', true)->save();
        // Set time out as 5 seconds.
        $autologout_settings->set('timeout', 5)->save();
        // Verify admin should not be logged out.
        $this->drupalGet('admin/reports/status');
        $this->assertSession()->statusCodeEquals('200');

        // Wait until timeout.
        sleep(20);

        // Verify admin should be logged out.
        $this->drupalGet('admin/reports/status');
        self::assertFalse($this->drupalUserIsLoggedIn($this->privilegedUser));
        $this->assertSession()->pageTextContains(
            $this->t('You have been logged out due to inactivity.')
        );
    }

    /**
     * Asserts the timeout for a particular user.
     *
     * @param int    $uid              User uid to assert the timeout for.
     * @param int    $expected_timeout The expected timeout.
     * @param string $message          The test message.
     *
     * @return object
     */
    public function assertAutotimeout($uid, $expected_timeout, $message = '')
    {
        self::assertEquals(
            $this->container->get('autologout.manager')->getUserTimeout($uid),
            $expected_timeout,
            $message
        );
    }

}
