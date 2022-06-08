<?php

namespace Drupal\Tests\securelogin\Functional;

use Drupal\Tests\BrowserTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Base class for Secure login module tests.
 *
 * @group Secure login
 */
abstract class SecureLoginTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Boolean true if Secure Login module should be installed.
   *
   * @var bool
   */
  protected $enableSecureLogin = TRUE;

  /**
   * Boolean true if the test environment is HTTPS.
   *
   * @var bool
   */
  protected $isSecure;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    if ($this->enableSecureLogin) {
      $this->container->get('module_installer')->install(['securelogin']);
      $this->rebuildContainer();
    }
    $this->isSecure = Request::createFromGlobals()->isSecure();
  }

  /**
   * Builds a URL for submitting a mock HTTPS request to HTTP test environments.
   */
  protected function httpsUrl($url) {
    return 'core/modules/system/tests/https.php/' . $url;
  }

  /**
   * Builds a URL for submitting a mock HTTP request to HTTPS test environments.
   */
  protected function httpUrl($url) {
    return 'core/modules/system/tests/http.php/' . $url;
  }

}
