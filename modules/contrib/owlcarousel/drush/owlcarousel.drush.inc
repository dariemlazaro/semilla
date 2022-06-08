<?php

/**
 * @file
 * Drush integration for owlcarousel.
 */

use Symfony\Component\Filesystem\Filesystem;

/**
 * The Owlcarousel2 plugin URI.
 */
define('OWLCAROUSEL_DOWNLOAD_URI', 'https://github.com/OwlCarousel2/OwlCarousel2/archive/master.zip');
define('OWLCAROUSEL_DOWNLOAD_PREFIX', 'owlcarousel-');

/**
 * Implements hook_drush_command().
 */
function owlcarousel_drush_command() {
  $items = [];

  // The key in the $items array is the name of the command.
  $items['owlcarousel-plugin'] = [
    'callback' => 'drush_owlcarousel_plugin',
    'description' => dt('Download and install the OwlCarousel2 plugin.'),
     // No bootstrap.
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
    'arguments' => [
      'path' => dt('Optional. A path where to install the OwlCarousel2 plugin. If omitted Drush will use the default location.'),
    ],
    'aliases' => ['owlcarouselplugin'],
  ];

  return $items;
}

/**
 * Implements hook_drush_help().
 *
 * This function is called whenever a drush user calls
 * 'drush help <name-of-your-command>'
 */
function owlcarousel_drush_help($section) {
  switch ($section) {
    case 'drush:owlcarousel-plugin':
      return dt('Download and install the Owlcarousel2 plugin from OwlCarousel2/OwlCarousel2, default location is the libraries directory.');
  }
}

/**
 * Command to download the Owlcarousel plugin.
 */
function drush_owlcarousel_plugin() {
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
  if ($filepath = drush_download_file(OWLCAROUSEL_DOWNLOAD_URI)) {
    $filename = basename($filepath);
    $dirname = OWLCAROUSEL_DOWNLOAD_PREFIX . basename($filepath, '.zip');

    // Remove any existing Owlcarousel2 plugin directory.
    if (is_dir($dirname) || is_dir('owlcarousel2')) {
      Filesystem::remove($dirname, TRUE);
      Filesystem::remove('owlcarousel2', TRUE);
      \Drupal::logger(dt('A existing Owlcarousel2 plugin was deleted from @path', ['@path' => $path]))->notice('notice');
    }

    // Decompress the zip archive.
    drush_tarball_extract($filename);

    // Change the directory name to "owlcarousel2" if needed.
    if ($dirname != 'owlcarousel2') {
      drush_move_dir($dirname, 'owlcarousel2', TRUE);
      $dirname = 'owlcarousel2';
    }
  }

  if (is_dir($dirname)) {
    \Drupal::logger(dt('Owlcarousel2 plugin has been installed in @path', ['@path' => $path]))->success('success');
  }
  else {
    \Drupal::logger(dt('Drush was unable to install the Owlcarousel2 plugin to @path', ['@path' => $path]))->error('error');
  }

  // Set working directory back to the previous working directory.
  chdir($olddir);
}
