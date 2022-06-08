<?php

/**
 * @file
 * Drush integration for PhotoSwipe.
 */

use Symfony\Component\Filesystem\Filesystem;

/**
 * The PhotoSwipe plugin URI.
 */
define('PHOTOSWIPE_DOWNLOAD_URL', 'https://github.com/dimsemenov/PhotoSwipe/archive/v4.1.0.zip');
define('PHOTOSWIPE_DOWNLOAD_PREFIX', 'PhotoSwipe-');

/**
 * Implements hook_drush_command().
 */
function photoswipe_drush_command() {
  $items = [];

  // The key in the $items array is the name of the command.
  $items['photoswipe-plugin'] = [
    'callback' => 'drush_photoswipe_plugin',
    'description' => dt('Download and install the PhotoSwipe plugin.'),
  // No bootstrap.
    'bootstrap' => DRUSH_BOOTSTRAP_NONE,
    'arguments' => [
      'path' => dt('Optional. A path where to install the PhotoSwipe plugin. If omitted Drush will use the default location.'),
    ],
    'aliases' => ['photoswipeplugin'],
  ];

  return $items;
}

/**
 * Implements hook_drush_help().
 */
function photoswipe_drush_help($section) {
  switch ($section) {
    case 'drush:photoswipe-plugin':
      return dt('Download and install the PhotoSwipe plugin from GitHub, default location is /libraries.');
  }
}

/**
 * Implements drush_MODULE_post_pm_enable().
 */
function drush_photoswipe_post_pm_enable() {
  $modules = func_get_args();
  if (in_array('photoswipe', $modules)) {
    drush_photoswipe_plugin();
  }
}

/**
 * Command to download the PhotoSwipe plugin.
 */
function drush_photoswipe_plugin() {
  $args = func_get_args();
  if (!empty($args[0])) {
    $path = $args[0];
  }
  else {
    $path = 'libraries';
  }

  // Create the path if it does not exist.
  if (!is_dir($path)) {
    drush_op('mkdir', $path);
    \Drupal::logger(dt('Directory @path was created', ['@path' => $path]))->notice('notice');
  }

  // Set the directory to the download location.
  $olddir = getcwd();
  chdir($path);

  // Download the zip archive.
  if ($filepath = drush_download_file(PHOTOSWIPE_DOWNLOAD_URL)) {
    $filename = basename($filepath);
    $dirname = PHOTOSWIPE_DOWNLOAD_PREFIX . ltrim(basename($filepath, '.zip'), 'v');

    // Remove any existing PhotoSwipe plugin directory.
    if (is_dir($dirname) || is_dir('photoswipe')) {
      Filesystem::remove($dirname, TRUE);
      Filesystem::remove('photoswipe', TRUE);
      \Drupal::logger(dt('A existing PhotoSwipe plugin was deleted from @path', ['@path' => $path]))->notice('notice');
    }

    // Decompress the zip archive.
    drush_tarball_extract($filename);

    // Change the directory name to "photoswipe" if needed.
    if ($dirname != 'photoswipe') {
      drush_move_dir($dirname, 'photoswipe', TRUE);
      $dirname = 'photoswipe';
    }
  }

  if (is_dir($dirname)) {
    \Drupal::logger(dt('PhotoSwipe plugin has been installed in @path', ['@path' => $path]))->success('success');
  }
  else {
    \Drupal::logger(dt('Drush was unable to install the PhotoSwipe plugin to @path', ['@path' => $path]))->error('error');
  }

  // Set working directory back to the previous working directory.
  chdir($olddir);
}
