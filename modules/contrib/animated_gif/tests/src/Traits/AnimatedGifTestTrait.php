<?php

declare(strict_types = 1);

namespace Drupal\Tests\animated_gif\Traits;

use Drupal\Core\File\FileSystemInterface;
use Drupal\file\FileInterface;

/**
 * Avoid code duplication between test base classes.
 */
trait AnimatedGifTestTrait {

  /**
   * File system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Module extension list.
   *
   * Currently no interface to rely on.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * Helper method to get the tested file.
   *
   * @param string $fileName
   *   The name of the file.
   * @param string $fileUri
   *   The Uri of the file.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @return \Drupal\file\FileInterface
   *   Return a file.
   */
  protected function getTestFile(string $fileName, string $fileUri): FileInterface {
    // Copy the source file to public directory.
    $source = $this->moduleExtensionList->getPath('animated_gif');
    $source .= '/tests/images/' . $fileName;
    $this->fileSystem->copy($source, $fileUri, FileSystemInterface::EXISTS_REPLACE);

    return $this->entityTypeManager->getStorage('file')->create([
      'filename' => $fileName,
      'filemime' => 'image/gif',
      'uri' => $fileUri,
    ]);
  }

}
