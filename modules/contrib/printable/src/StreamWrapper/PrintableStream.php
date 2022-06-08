<?php

namespace Drupal\printable\StreamWrapper;

use Drupal\Core\StreamWrapper\LocalStream;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;

/**
 * Drupal stream wrapper base class for files to be embedded in the PDF.
 */
class PrintableStream extends LocalStream implements StreamWrapperInterface {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return t('Local file paths for PDF generation');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Files to be included in a PDF');
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl() {
    return 'printable://' . $this->getTarget();
  }

  /**
   * {@inheritdoc}
   */
  public function getDirectoryPath() {
    return static::basePath();
  }

  /**
   * {@inheritdoc}
   */
  public static function basePath() {
    return '';
  }

  /**
   * Returns the local writable target of the resource within the stream.
   *
   * This function should be used in place of calls to realpath() or similar
   * functions when attempting to determine the location of a file. While
   * functions like realpath() may return the location of a read-only file, this
   * method may return a URI or path suitable for writing that is completely
   * separate from the URI used for reading.
   *
   * @param string $uri
   *   Optional URI.
   *
   * @return string|bool
   *   Returns a string representing a location suitable for writing of a file,
   *   or FALSE if unable to write to the file such as with read-only streams.
   */
  protected function getTarget($uri = NULL) {
    if (!isset($uri)) {
      $uri = $this->uri;
    }

    [, $target] = explode('://', $uri, 2);

    return $target;
  }

}
