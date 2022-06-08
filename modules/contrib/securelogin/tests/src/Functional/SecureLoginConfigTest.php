<?php

namespace Drupal\Tests\securelogin\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Tests secure login module configuration.
 *
 * @group Secure login
 */
class SecureLoginConfigTest extends SecureLoginTestBase {

  use StringTranslationTrait;

  const BASE_URL = 'https://securelogin.test';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // We cannot login to HTTP site if Secure Login is installed.
    if (!$this->isSecure) {
      $this->config('securelogin.settings')->set('base_url', self::BASE_URL)->save();
      return;
    }
    $web_user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($web_user);
    $this->drupalGet('admin/config/people/securelogin');
    $fields['base_url'] = self::BASE_URL;
    $this->submitForm($fields, $this->t('Save configuration'));
  }

  /**
   * Ensure redirects use the configured base URL.
   */
  public function testSecureLoginBaseUrl() {
    // Disable redirect following.
    if (method_exists($this->getSession()->getDriver(), 'getClient')) {
      $this->getSession()->getDriver()->getClient()->followRedirects(FALSE);
    }
    $maximumMetaRefreshCount = $this->maximumMetaRefreshCount;
    $this->maximumMetaRefreshCount = 0;
    $this->drupalGet($this->httpUrl('user/login'));
    $this->assertSession()->statusCodeEquals(301);
    $this->assertSame(0, strpos($this->getSession()->getResponseHeader('Location'), self::BASE_URL . '/user/login'), 'Location header uses the configured secure base URL.');
    if (method_exists($this->getSession()->getDriver(), 'getClient')) {
      $this->getSession()->getDriver()->getClient()->followRedirects(TRUE);
    }
    $this->maximumMetaRefreshCount = $maximumMetaRefreshCount;
  }

}
