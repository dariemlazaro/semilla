<?php

/**
 * @file
 * WatchdogAdapter class.
 */

namespace Drupal\db_maintenance\Module\Common;

use Psr\Log\LogLevel;

/**
 * WatchdogAdapter class.
 */
class WatchdogAdapter {

  /**
   * Simulates D7 watchdog function.
   */
  public static function watchdog($type, $message, $variables = array(), $severity = LogLevel::NOTICE) {
    \Drupal::logger($type)->log($severity, $message, $variables);
  }

}
