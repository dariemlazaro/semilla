<?php

namespace Drupal\high_contrast;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Render\Element\RenderCallbackInterface;

/**
 * Provides a trusted callback to alter the system branding block.
 *
 * @see color_block_view_system_branding_block_alter()
 */
class HighContrastBlockView implements RenderCallbackInterface {

  /**
   * #pre_render callback: Sets high contrast cacheability metadata on blocks.
   *
   * Scans all blocks to see if they depend on the system.theme.global cache tag.
   * If so, also make them depend on the high contrast cacheability metadata.
   */
  public static function preRender(array $build) {
    $cacheable_metadata = CacheableMetadata::createFromRenderArray($build);

    // Add cacheable data for blocks depending on config:system.site cache tags.
    // Todo: The system branding block does not correctly declare its dependency
    // on system.theme.global.
    $tags = $cacheable_metadata->getCacheTags();
    if (in_array('config:system.site', $tags)) {
      $config = \Drupal::config('high_contrast.settings');

      // Always create a cache context for the no-whitelabel version.
      $cacheable_metadata->addCacheContexts(['high_contrast']);
      $cacheable_metadata->addCacheableDependency($config);

      // Add cacheable metadata.
      $cacheable_metadata->applyTo($build);
    }

    return $build;
  }

}
