<?php

namespace Drupal\Tests\simple_live_css\FunctionalJavascript;

use Behat\Mink\Session;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Class SimpleLiveCssTest.
 *
 * @group simple_live_css
 */
class SimpleLiveCssTest extends WebDriverTestBase {

  /**
   * The user to use during testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'simple_live_css',
    'test_page_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->user = $this->drupalCreateUser([
      'edit live css',
    ]);

    $this->drupalLogin($this->user);
  }

  /**
   * Tests syncing of live css - without saving.
   */
  public function testLiveCssSync() {

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('/test-page');
    $button = $page->findButton('LIVE CSS');
    $this->assertTrue($button->isVisible());

    // Open the live css editor.
    $button->click();

    // Wait for the editor to fully initialize.
    $assert_session->waitForElementVisible('css', '.live-css-editor-container');

    // Enter some test css inside the editor.
    $page->find('css', '.ace_text-input')->setValue('html { background-color: red; }');

    // Check if the styling rules in the live css editor are synced to the page.
    $this->assertComputedBackgroundColor('rgb(255, 0, 0)');

    // Close the editor. This should not trigger a page
    // refresh, since there were no CSS changes saved.
    $page->find('css', '.js--live-css-close')->click();
    $assert_session->waitForElementRemoved('css', '.js--live-css-editor-container');

    // When the editor is closed - without any CSS being
    // saved - styling changes should not be visible anymore.
    $this->assertComputedBackgroundColor('rgba(0, 0, 0, 0)');

  }

  /**
   * Tests saving of live css.
   */
  public function testLiveCssSave() {

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('/test-page');
    $button = $page->findButton('LIVE CSS');
    $this->assertTrue($button->isVisible());

    // Open the live css editor.
    $button->click();

    // Wait for the editor to fully initialize.
    $assert_session->waitForElementVisible('css', '.js--live-css-editor-container');

    // Enter some test css inside the editor.
    $page->find('css', '.ace_text-input')->setValue('html { background-color: green; }');

    // Save the entered css rules.
    $page->find('css', '.js--live-css-save')->click();

    // Wait for ajax to finish.
    $assert_session->assertWaitOnAjaxRequest();

    // Check if the styling rules in the editor were synced to the page.
    $this->assertComputedBackgroundColor('rgb(0, 128, 0)');

    // Close the CSS editor.
    $page->find('css', '.js--live-css-close')->click();

    // Check if the styling rules in the editor are visible after page refresh.
    $this->assertComputedBackgroundColor('rgb(0, 128, 0)');
  }

  /**
   * Validate the background color being applied to the page.
   *
   * @param string $expected_bg_color
   *   The expected background color.
   */
  protected function assertComputedBackgroundColor($expected_bg_color) {
    $this->printComputedBackgroundColorToPage($this->getSession());
    $computed_bg_color = $this->getSession()->getPage()->find('css', '.computed-bg-color')->getText();
    $this->assertEqual($computed_bg_color, $expected_bg_color);
  }

  /**
   * Print the computed background color to the page in a span element.
   *
   * Since there does not seem to be a native way to check for
   * computed css properties; extract the background-color and
   * display it to the page in a span. This way we can still
   * validate the computed style value.
   *
   * If the span already exists on the page, its contents will be overwritten.
   *
   * @param \Behat\Mink\Session $session
   *   The mink session.
   */
  protected function printComputedBackgroundColorToPage(Session $session) {
    $session->executeScript("
      var html = document.querySelector('html');
      var computed_style = window.getComputedStyle(html);
      var bg_color = computed_style.getPropertyValue('background-color');
      var bg_color_span = document.querySelector('.computed-bg-color');
      if (!bg_color_span) {
        bg_color_span = document.createElement('span');
        bg_color_span.classList.add('computed-bg-color');
      }

      bg_color_span.innerHTML = bg_color;
      html.appendChild(bg_color_span);
    ");
  }

}
