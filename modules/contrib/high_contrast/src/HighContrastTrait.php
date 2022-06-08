<?php

namespace Drupal\high_contrast;

/**
 * This is an abstraction wrapper for controlling high contrast.
 *
 * This class is provided to allow abstraction of the detection and setting of
 * high contrast mode. Currently it is configured to use the session, but this
 * allows for easier change if that may be required at some point.
 */
trait HighContrastTrait {

  /**
   * Return if high contrast is enabled or not.
   *
   * @return bool
   *   TRUE if enabled, FALSE otherwise.
   */
  public static function highContrastEnabled() {
    return !empty($_SESSION['high_contrast']['enabled']) ? TRUE : FALSE;
  }

  /**
   * Enables high contrast mode.
   */
  public function enableHighContrast() {
    $_SESSION['high_contrast']['enabled'] = TRUE;
  }

  /**
   * Disables high contrast mode.
   */
  public function disableHighContrast() {
    $_SESSION['high_contrast']['enabled'] = FALSE;
  }

}
