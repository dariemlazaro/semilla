<?php

namespace Drupal\securelogin;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;

/**
 * Defines the SecureLoginCacheableDependency.
 */
class SecureLoginCacheableDependency implements CacheableDependencyInterface {

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['securelogin'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

}
