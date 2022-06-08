<?php

/**
 * @file
 * Contains \Drupal\pdf_api\Plugin\PdfGeneratorInterface.
 */

namespace Drupal\pdf_api\Plugin;

use Drupal\Core\Entity\EntityInterface;

/**
 * Defines an interface for PDF generator plugins.
 */
interface PdfGeneratorInterface {

  /**
   * Landscape paper orientation.
   */
  const LANDSCAPE = 'landscape';

  /**
   * Portrait paper orientation.
   */
  const PORTRAIT = 'portrait';

  /**
   * Set the various options for PDF.
   *
   * @param string $pdf_content
   *   The HTML content of PDF.
   * @param string $pdf_location
   *   The location where PDF needs to be saved.
   * @param bool $save_pdf
   *   Stores the configuration whether PDF needs to be saved or shown inline.
   * @param string $paper_orientation
   *   The orientation of PDF pages (portrait or landscape).
   * @param string $paper_size
   *   The size of PDF paper (e.g. A4, B4, Letter).
   * @param string $footer_content
   *   The text to be rendered as footer.
   * @param string $header_content
   *   The text to be rendered as header.
   * @param string $path_to_binary
   *   The path to binary file.
   */
  public function setter($pdf_content, $pdf_location, $save_pdf, $paper_orientation, $paper_size, $footer_content, $header_content, $path_to_binary = '');

  /**
   * Returns the administrative id for this generator plugin.
   *
   * @return string
   *   The id of the plugin.
   */
  public function getId();

  /**
   * Returns the administrative label for this generator plugin.
   *
   * @return string
   *   The label of the plugin.
   */
  public function getLabel();

  /**
   * Returns the administrative description for this generator plugin.
   *
   * @return string
   *   The description about the plugin.
   */
  public function getDescription();

  /**
   * Returns instances of PDF libraries.
   *
   * @return object
   *   The object of PDF generating libraries.
   */
  public function getObject();

  /**
   * Sets the header in the PDF.
   *
   * @param string $html
   *   The text to be rendered as header.
   */
  public function setHeader($html);

  /**
   * Set the paper orientation of the generated PDF pages.
   *
   * @param PdfGeneratorInterface::PORTRAIT|PdfGeneratorInterface::LANDSCAPE $orientation
   *   The orientation of the PDF pages.
   */
  public function setPageOrientation($orientation = PdfGeneratorInterface::PORTRAIT);

  /**
   * Set the page size of the generated PDF pages.
   *
   * @param string $page_size
   *   The page size (e.g. A4, B2, Letter).
   */
  public function setPageSize($page_size);

  /**
   * Add a page to the generated PDF.
   *
   * @param string $html
   *   The HTML of the page to be added.
   */
  public function addPage($html);

  /**
   * Generate and save the PDF at a specific location.
   *
   * @param string $location
   *   The location (both absolute/relative) path to save the generated PDF to.
   */
  public function save($location);

  /**
   * The name of the file to be downloaded.
   */
  public function send();

  /**
   * Stream the PDF to the browser.
   */
  public function stream($filelocation);

  /**
   * Sets the footer in the PDF.
   *
   * @param string $html
   *   The text to be rendered as footer.
   */
  public function setFooter($html);

  /**
   * Get stderr from the command that was run.
   *
   * @param string $string
   *   The output sent to stderr.
   */
  public function getStderr();

  /**
   * Get stdout from the command that was run.
   *
   * @param string $html
   *   The text to be rendered as footer.
   */
  public function getStdout();

  /**
   * Display error messages.
   *
   * @return boolean
   *   Whether any errors occurred and were not ignored.
   */
  public function displayErrors();

  /**
   * Use Printable display mode?
   *
   * We normally generate the HTML using buildPdfContent and a render call.
   * That doesn't let JS run and may limit the CSS that's used. A PDF generator
   * can instead override this function (which returns FALSE in the base
   * implementation) and return true, it will then need to use the printable
   * entity URL to get content.
   *
   * @return bool
   *   Whether we are using the default entity print rendering method.
   */
  public function usePrintableDisplay();

  /**
   * Set the entity being displayed by the generator.
   *
   * @param \Drupal\Core\Entity\EntityInterface
   *   The entity being rendered.
   */
  public function setEntity(EntityInterface $entity);

  /**
   * Provide the entity being displayed to the generator.
   *
   * If the generator is not using printable's display (eg it is using a view
   * mode on the entity) this is how it gets access.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity being rendered.
   */
  public function getEntity();

}
