<?php

namespace Drupal\owlcarousel\Commands;

use Drupal\Core\Asset\libraryDiscovery;
use Drush\Commands\DrushCommands;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://git.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://git.drupalcode.org/devel/tree/drush.services.yml
 */
class OwlCarouselCommands extends DrushCommands {

  /**
   * Library discovery service.
   *
   * @var Drupal\Core\Asset\libraryDiscovery
   */
  protected $libraryDiscovery;

  /**
   * {@inheritdoc}
   */
  public function __construct(libraryDiscovery $library_discovery) {
    $this->libraryDiscovery = $library_discovery;
  }

  /**
   * Download and install the OwlCarousel2 plugin.
   *
   * @param mixed $path
   *   Optional. A path where to install the Owlcarousel2 plugin.
   *   If omitted Drush will use the default location.
   *
   * @command owlcarousel2:plugin
   * @aliases owlcarousel2plugin,owlcarousel2-plugin
   */
  public function download($path = '') {

    $fs = new Filesystem();

    if (empty($path)) {
      $path = DRUPAL_ROOT . '/libraries/owlcarousel2';
    }

    // Create path if it doesn't exist
    // If exits delete and recreate with a message otherwise.
    if (!$fs->exists($path)) {
      $fs->mkdir($path);
    }
    else {
      $fs->remove($path);
      $this->logger()->notice(dt('An existing Owlcarousel2 plugin is deleted from @path and it will be reinstalled again.', ['@path' => $path]));
      $fs->mkdir($path);
    }

    // Load the owlcarousel defined library.
    if ($owlcarousel_library = $this->libraryDiscovery->getLibraryByName('owlcarousel', 'owlcarousel')) {
      // Download the file.
      $client = new Client();
      $destination = tempnam(sys_get_temp_dir(), 'owlcarousel-tmp');
      try {
        $client->get($owlcarousel_library['remote'] . '/archive/master.zip', ['save_to' => $destination]);
      }
      catch (RequestException $e) {
        // Remove the directory.
        $fs->remove($path);
        $this->logger()->error(dt('Drush was unable to download the owlcarousel library from @remote. @exception', [
          '@remote' => $owlcarousel_library['remote'] . '/archive/master.zip',
          '@exception' => $e->getMessage(),
        ], 'error'));
        return;
      }

      // Move downloaded file.
      $fs->rename($destination, $path . '/owlcarousel.zip');

      // Unzip the file.
      $zip = new \ZipArchive();
      $res = $zip->open($path . '/owlcarousel.zip');
      if ($res === TRUE) {
        $zip->extractTo($path);
        $zip->close();
      }
      else {
        // Remove the directory if unzip fails and exit.
        $fs->remove($path);
        $this->logger()->error(dt('Error: unable to unzip owlcarousel file.', [], 'error'));
        return;
      }

      // Remove the downloaded zip file.
      $fs->remove($path . '/owlcarousel.zip');

      // Move the file.
      $fs->mirror($path . '/OwlCarousel2-master', $path, NULL, ['override' => TRUE]);
      $fs->remove($path . '/OwlCarousel2-master');

      // Success.
      $this->logger()->notice(dt('The owlcarousel library has been successfully downloaded to @path.', [
        '@path' => $path,
      ], 'success'));
    }
    else {
      $this->logger()->error(dt('Drush was unable to load the owlcarousel library'));
    }
  }

}
