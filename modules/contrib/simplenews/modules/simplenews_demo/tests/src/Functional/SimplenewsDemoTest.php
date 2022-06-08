<?php

namespace Drupal\Tests\simplenews_demo\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the demo module for Simplenews.
 *
 * @group simplenews
 */
class SimplenewsDemoTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = [];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Install bartik theme.
    \Drupal::service('theme_installer')->install(['bartik']);
    $theme_settings = $this->config('system.theme');
    $theme_settings->set('default', 'bartik')->save();
    // Install simplenews_demo module.
    \Drupal::service('module_installer')->install(['simplenews_demo']);
    // Log in with all relevant permissions.
    $this->drupalLogin($this->drupalCreateUser([
      'administer simplenews subscriptions', 'send newsletter', 'administer newsletters', 'administer simplenews settings',
    ]));
  }

  /**
   * Asserts the demo module has been installed successfully.
   */
  public function testInstalled() {
    // Check for the two subscription blocks.
    $this->assertText('Simplenews multiple subscriptions');
    $this->assertText('Stay informed - subscribe to our newsletters.');
    $this->assertText('Simplenews subscription');
    $this->assertText('Stay informed - subscribe to our newsletter.');

    $this->drupalGet('admin/config/services/simplenews');
    $this->clickLink(t('Edit'));
    // Assert default description is present.
    $this->assertEquals('This is an example newsletter. Change it.', $this->xpath('//textarea[@id="edit-description"]')[0]->getText());
    $from_name = $this->xpath('//input[@id="edit-from-name"]')[0];
    $from_address = $this->xpath('//input[@id="edit-from-address"]')[0];
    $this->assertEquals('Drupal', (string) $from_name->getValue());
    $this->assertEquals('simpletest@example.com', (string) $from_address->getValue());
    // Assert demo newsletters.
    $this->drupalGet('admin/config/services/simplenews');
    $this->assertText(t('Press releases'));
    $this->assertText(t('Special offers'));
    $this->assertText(t('Weekly content update'));
    // Assert demo newsletters sent.
    $this->drupalGet('admin/content/simplenews');
    // @codingStandardsIgnoreLine
    //$this->assertText('Scheduled weekly content newsletter issue');
    $this->assertText('Sent press releases');
    $this->assertText('Unpublished press releases');
    $this->assertText('Pending special offers');
    $this->assertText('Stopped special offers');
    // @codingStandardsIgnoreLine
    //$this->assertText('Scheduled weekly content newsletter issue - Week ');
    $this->assertRaw(t('Newsletter issue sent to 2 subscribers, 0 errors.'));
    $this->assertRaw(t('Newsletter issue is pending, 0 mails sent out of 3, 0 errors.'));
    // Weekly newsletter.
    // @codingStandardsIgnoreLine
    //$this->assertRaw(t('Newsletter issue sent to 1 subscribers, 0 errors.'));
    // Assert demo subscribers.
    $this->drupalGet('admin/people/simplenews');
    $this->assertText('a@example.com');
    $this->assertText('b@example.com');
    $this->assertText('demouser1@example.com');
  }

}
