<?php

namespace Drupal\high_contrast\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\high_contrast\HighContrastTrait;

/**
 * Defines the HighContrastCacheContext service.
 *
 * This allows caching of high and normal contrast versions of pages.
 */
class HighContrastCacheContext implements CacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('High contrast');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return HighContrastTrait::highContrastEnabled();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
