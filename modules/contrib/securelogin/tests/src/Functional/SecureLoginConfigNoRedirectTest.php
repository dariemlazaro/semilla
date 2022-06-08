<?php

namespace Drupal\Tests\securelogin\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Tests secure login module configuration.
 *
 * @group Secure login
 */
class SecureLoginConfigNoRedirectTest extends SecureLoginConfigTest {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // We cannot login to HTTP site if Secure Login is installed.
    if (!$this->isSecure) {
      $this->config('securelogin.settings')->set('secure_forms', FALSE)->save();
      return;
    }
    $this->drupalGet('admin/config/people/securelogin');
    $fields['secure_forms'] = FALSE;
    $this->submitForm($fields, $this->t('Save configuration'));
  }

  /**
   * Ensure redirects use the configured base URL.
   */
  public function testSecureLoginBaseUrl() {
    $this->drupalGet($this->httpUrl('user/login'));
    $this->assertSession()->responseContains('<form class="user-login-form" data-drupal-selector="user-login-form" action="' . self::BASE_URL . '/index.php/user/login" method="post" id="user-login-form" accept-charset="UTF-8">');
  }

}
