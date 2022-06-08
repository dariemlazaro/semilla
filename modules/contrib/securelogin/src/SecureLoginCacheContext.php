<?php

namespace Drupal\securelogin;

use Drupal\Core\Cache\Context\UrlCacheContext;

/**
 * Defines the SecureLoginCacheContext service for "per request URL" caching.
 *
 * Cache context ID: 'securelogin'.
 */
class SecureLoginCacheContext extends UrlCacheContext {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Secure login');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    $method = method_exists($this->requestStack, 'getMainRequest') ? 'getMainRequest' : 'getMasterRequest';
    return $this->requestStack->$method()->getUri();
  }

}
