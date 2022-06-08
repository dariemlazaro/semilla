<?php

namespace Drupal\Tests\securelogin\Functional;

/**
 * Tests Secure login with user login block enabled.
 *
 * @group Secure login
 */
class SecureLoginBlockCacheTest extends SecureLoginBlockTest {

  /**
   * Use a profile that enables the cache modules.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $config = $this->config('system.performance');
    $config->set('cache.page.max_age', 3600);
    $config->save();
  }

}
