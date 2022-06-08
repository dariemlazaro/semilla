<?php

namespace Drupal\photoswipe;

/**
 * Interface for photoswipe assets manager.
 */
interface PhotoswipeAssetsManagerInterface {

  /**
   * Attach photoswipe assets.
   *
   * @param array $element
   *   The render array to attach photoswipe assets to.
   */
  public function attach(array &$element);

  /**
   * Are photoswipe assets attached to this page in this request?.
   *
   * @return bool
   *   Whether photoswipe assets attached to this page or not.
   */
  public function isAttached();

}
