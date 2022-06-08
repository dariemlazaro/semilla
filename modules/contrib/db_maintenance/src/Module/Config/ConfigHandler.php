<?php

/**
 * @file
 * ConfigHandler class.
 */

namespace Drupal\db_maintenance\Module\Config;

/**
 * ConfigHandler class.
 */
class ConfigHandler {

  /**
   * Returns last cron run.
   */
  public static function getCronLastRun() {
    $val = \Drupal::config('db_maintenance.settings')->get('cron_last_run');
    return $val;
  }

  /**
   * Sets last cron run.
   */
  public static function setCronLastRun($value) {
    $config = \Drupal::configFactory()->getEditable('db_maintenance.settings');
    $config->set('cron_last_run', $value);
    $config->save();
  }

  /**
   * Returns cron frequency.
   */
  public static function getCronFrequency() {
    $val = \Drupal::config('db_maintenance.settings')->get('cron_frequency');
    return $val;
  }

  /**
   * Returns Log config value.
   */
  public static function getWriteLog() {
    $val = \Drupal::config('db_maintenance.settings')->get('write_log');
    return $val;
  }

  /**
   * Returns UseTimeInterval variable.
   */
  public static function getUseTimeInterval() {
    $val = \Drupal::config('db_maintenance.settings')->get('use_time_interval');
    return $val;
  }

  /**
   * Returns TimeIntervalStart variable.
   */
  public static function getTimeIntervalStart() {
    $val = \Drupal::config('db_maintenance.settings')->get('time_interval_start');
    return $val;
  }

  /**
   * Returns TimeIntervalEnd variable.
   */
  public static function getTimeIntervalEnd() {
    $val = \Drupal::config('db_maintenance.settings')->get('time_interval_end');
    return $val;
  }

  /**
   * Returns AllTables config value.
   */
  public static function getProcessAllTables() {
    $val = \Drupal::config('db_maintenance.settings')->get('all_tables');
    return $val;
  }

  /**
   * Returns TableList config value.
   */
  public static function getTableList($database, $default = NULL) {
    $val = \Drupal::config('db_maintenance.settings')->get('table_list_' . $database);
    if (is_null($val)) {
      // This config key does not exist.
      return $default;
    }
    return $val;
  }

}
