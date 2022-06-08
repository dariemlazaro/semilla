<?php

namespace Drupal\Tests\fb_likebox\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests if the facebook likebox block is available.
 *
 * @group fb_likebox
 */
class FBLikeboxBlockTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system_test', 'block', 'fb_likebox'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create and login user.
    $admin_user = $this->drupalCreateUser([
      'administer blocks', 'administer site configuration',
      'access administration pages',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Test that the sharethis form block can be placed and works.
   */
  public function testFBLikeboxBlock() {
    // Test availability of the fb_likebox block in the admin "Place blocks" list.
    \Drupal::service('theme_handler')->install(['bartik', 'seven', 'stark']);
    $theme_settings = $this->config('system.theme');
    foreach (['bartik', 'seven', 'stark'] as $theme) {
      $this->drupalGet('admin/structure/block/list/' . $theme);
      // Configure and save the block.
      $this->drupalPlaceBlock('fb_likebox_block', [
        'url' => 'https://www.facebook.com/FacebookDevelopers',
        'title' => 'Iframe Title',
        'width' => 180,
        'height' => 70,
        'language' => 'en_IN',
        'region' => 'content',
        'theme' => $theme,
      ]);
      // Set the default theme and ensure the block is placed.
      $theme_settings->set('default', $theme)->save();
      $this->drupalGet('');
      $result = $this->xpath('//div[@class=:class]', [':class' => 'fb-page']);
      $this->assertEqual(count($result), 1, 'Facebook Likebox block found');
    }
  }
}
