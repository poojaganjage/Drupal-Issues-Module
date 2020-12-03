<?php

namespace Drupal\Tests\autologout\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Tests the autologout's features.
 */
class AutologoutTImeSetTest extends BrowserTestBase
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
     * Tests the precedence of the timeouts.
     *
     * This tests the following function:
     *  _autologout_get_user_timeout();
     *
     * @return object
     */
    public function testAutologoutTimeoutPrecedence()
    {
        $autologout_settings = $this->configFactory
            ->getEditable('autologout.settings');
        $autologout_role_settings = $this->configFactory
            ->getEditable('autologout.role.authenticated');
        $uid = $this->privilegedUser->id();
        $autologout_user_settings = $this->container->get('user.data');

        // Default used if no role is specified.
        $autologout_settings->set('timeout', 100)
            ->set('role_logout', false)
            ->save();
        $autologout_role_settings->set('enabled', false)
            ->set('timeout', 200)
            ->save();
        $this->assertAutotimeout(
            $uid,
            100,
            'User timeout uses default if no other option set'
        );

        // Default used if role selected but no user's role is selected.
        $autologout_settings->set('role_logout', true)->save();
        $autologout_role_settings->set('enabled', false)
            ->set('timeout', 200)
            ->save();
        $this->assertAutotimeout(
            $uid,
            100,
            'User timeout uses default if role timeouts
            are used but not one of the current user.'
        );

        // Role timeout is used if user's role is selected.
        $autologout_settings->set('role_logout', true)->save();
        $autologout_role_settings->set('enabled', true)
            ->set('timeout', 200)
            ->save();
        $this->assertAutotimeout($uid, 200, 'User timeout uses role value');

        // Role timeout is used if user's role is selected.
        $autologout_settings->set('role_logout', true)->save();
        $autologout_role_settings->set('enabled', true)
            ->set('timeout', 0)
            ->save();
        $this->assertAutotimeout(
            $uid,
            0,
            'User timeout uses role value of 0 if set for one of the user roles.'
        );

        // Role timeout used if personal timeout is empty string.
        $autologout_settings->set('role_logout', true)->save();
        $autologout_role_settings->set('enabled', true)
            ->set('timeout', 200)
            ->save();
        $autologout_user_settings->set('autologout', $uid, 'timeout', '');
        $autologout_user_settings->set('autologout', $uid, 'enabled', false);
        $this->assertAutotimeout(
            $uid,
            200,
            'User timeout uses role value if personal value is the empty string.'
        );

        // Default timeout used if personal timeout is empty string.
        $autologout_settings->set('role_logout', true)->save();
        $autologout_role_settings->set('enabled', false)
            ->set('timeout', 200)
            ->save();
        $autologout_user_settings->set('autologout', $uid, 'timeout', '');
        $autologout_user_settings->set('autologout', $uid, 'enabled', false);
        $this->assertAutotimeout(
            $uid,
            100,
            'User timeout uses default value if personal value
            is the empty string and no role timeout is specified.'
        );

        // Personal timeout used if set.
        $autologout_settings->set('role_logout', true)->save();
        $autologout_role_settings->set('enabled', false)
            ->set('timeout', 200)
            ->save();
        $autologout_user_settings->set('autologout', $uid, 'timeout', 300);
        $autologout_user_settings->set('autologout', $uid, 'enabled', true);
        $this->assertAutotimeout(
            $uid,
            300,
            'User timeout uses default value if personal value
            is the empty string and no role timeout is specified.'
        );
    }

    /**
     * Tests the behaviour of the settings for submission.
     *
     * @return object
     */
    public function testAutologoutSettingsForm()
    {
        $edit = [];
        $autologout_settings = $this->configFactory
            ->getEditable('autologout.settings');
        $autologout_settings->set('max_timeout', 1000)->save();

        $roles = user_roles(true);
        // Unset authenticated, as it uses the default timeout value.
        unset($roles['authenticated']);

        // Test that it is possible to set a value above the max_timeout
        // threshold.
        $edit['timeout'] = 1500;
        $edit['max_timeout'] = 2000;
        $edit['padding'] = 60;
        $edit['role_logout'] = true;
        foreach ($roles as $key => $role) {
            $edit['table[' . $key . '][enabled]'] = true;
            $edit['table[' . $key . '][timeout]'] = 1200;
            $edit['table[' . $key . '][url]'] = '/user/login';
        }
        $edit['redirect_url'] = '/user/login';

        $this->drupalPostForm(
            'admin/config/people/autologout',
            $edit,$this->t('Save configuration')
        );
        $this->assertSession()->pageTextContains(
            $this->t('The configuration options have been saved.')
        );

        // Test that out of range values are picked up.
        $edit['timeout'] = 2500;
        $edit['max_timeout'] = 2000;
        $edit['padding'] = 60;
        $edit['role_logout'] = true;
        foreach ($roles as $key => $role) {
            $edit['table[' . $key . '][enabled]'] = true;
            $edit['table[' . $key . '][timeout]'] = 1200;
            $edit['table[' . $key . '][url]'] = '/user/login';
        }
        $edit['redirect_url'] = '/user/login';
        $this->drupalPostForm(
            'admin/config/people/autologout',
            $edit, $this->t('Save configuration')
        );
        $this->assertSession()->pageTextNotContains(
            $this->t('The configuration options have been saved.')
        );

        // Test that out of range values are picked up.
        $edit['timeout'] = 1500;
        $edit['max_timeout'] = 2000;
        $edit['padding'] = 60;
        $edit['role_logout'] = true;
        foreach ($roles as $key => $role) {
            $edit['table[' . $key . '][enabled]'] = true;
            $edit['table[' . $key . '][timeout]'] = 2500;
            $edit['table[' . $key . '][url]'] = '/user/login';
        }
        $edit['redirect_url'] = '/user/login';
        $this->drupalPostForm(
            'admin/config/people/autologout',
            $edit, $this->t('Save configuration')
        );
        $this->assertSession()->pageTextNotContains(
            $this->t('The configuration options have been saved.')
        );

        // Test that role timeouts are not validated for disabled roles.
        $edit['timeout'] = 1500;
        $edit['max_timeout'] = 2000;
        $edit['padding'] = 60;
        $edit['role_logout'] = true;
        foreach ($roles as $key => $role) {
            $edit['table[' . $key . '][enabled]'] = false;
            $edit['table[' . $key . '][timeout]'] = 1200;
            $edit['table[' . $key . '][url]'] = '/user/login';
        }
        $edit['redirect_url'] = '/user/login';

        $this->drupalPostForm(
            'admin/config/people/autologout',
            $edit, $this->t('Save configuration')
        );
        $this->assertSession()->pageTextContains(
            $this->t('The configuration options have been saved.')
        );
        $this->drupalPostForm(
            'admin/config/people/autologout',
            $edit, $this->t('Save configuration')
        );
        $this->assertText(
            $this->t('The configuration options have been saved.'),
            'Unable to save autologout due to out of range role timeout
            for a role which is not enabled..'
        );

        // Test clearing of users individual timeout when this becomes disabled.
        $uid = $this->privilegedUser->id();
        // Activate individual user timeout for user.
        $this->userData->set('autologout', $uid, 'timeout', 1600);

        // Turn off individual settings.
        $edit['no_individual_logout_threshold'] = true;
        $this->drupalPostForm(
            'admin/config/people/autologout',
            $edit, $this->t('Save configuration')
        );

        // Expected is that default value is returned, not user-overriden value.
        $this->assertAutotimeout(
            $uid, 1500, 'User timeout
            is cleared when setting no_individual_logout_threshold
            is activated.'
        );
    }

}
