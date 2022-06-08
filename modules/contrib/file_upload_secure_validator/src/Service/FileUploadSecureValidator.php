<?php

namespace Drupal\file_upload_secure_validator\Service;

use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser as SymfonyFileinfoMimeTypeGuesser;

/**
 * A service class for fileinfo-based validation.
 */
class FileUploadSecureValidator {

  /**
   * Logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerChannelFactory;

  /**
   * String translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translator;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs the file upload secure validation service.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service object.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translator
   *   The string translation service object.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service object.
   *
   * @return void
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory,
    TranslationInterface $translator,
    ConfigFactoryInterface $config_factory) {
    $this->loggerChannelFactory = $logger_factory;
    $this->translator = $translator;
    $this->configFactory = $config_factory;
  }

  /**
   * File validation function.
   *
   * @param \Drupal\file\Entity\File $file
   *   The file to be uploaded.
   */
  public function validate(File $file) {
    // Get mime type from filename.
    $mimeByFilename = $file->getMimeType();
    // Get mime type from fileinfo.
    $mimeByFileinfo = (new SymfonyFileinfoMimeTypeGuesser())->guess($file->getFileUri());

    // Early exit, fileinfo agrees with the file's extension.
    if ($mimeByFilename === $mimeByFileinfo) {
      return [];
    }

    // Check against known MIME types equivalence groups.
    $mimeTypesGroups = $this->configFactory->get('file_upload_secure_validator.settings')
      ->get('mime_types_equivalence_groups');
    // Exit when a mime-type equivalence pairing is found.
    foreach ($mimeTypesGroups as $mimeTypesGroup) {
      if (empty(array_diff(
        [
          $mimeByFilename,
          $mimeByFileinfo,
        ], $mimeTypesGroup))) {
        return [];
      }
    }

    // Log disagreement.
    $this->loggerChannelFactory->get('file_upload_secure_validator')
      ->error("Error while uploading file: MimeTypeGuesser guessed '%mime_by_fileinfo' and fileinfo '%mime_by_filename'", [
        '%mime_by_fileinfo' => $mimeByFileinfo,
        '%mime_by_filename' => $mimeByFilename,
      ]);

    // Return error.
    return [
      new TranslatableMarkup('There was a problem with this file. The uploaded file is of type @extension but the real content seems to be @real_extension', [
        '@extension' => $mimeByFilename,
        '@real_extension' => $mimeByFileinfo,
      ], [], $this->translator),
    ];
  }

}
