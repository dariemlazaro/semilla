<?php

/**
 * @file
 * Contains \Drupal\pdf_api\Plugin\TcpdfGenerator.
 */

namespace Drupal\pdf_api\Plugin\PdfGenerator;

use Drupal\pdf_api\Plugin\PdfGeneratorBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\pdf_api\Annotation\PdfGenerator;
use Drupal\Core\Annotation\Translation;
use \TCPDF;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A PDF generator plugin for the mPDF library.
 *
 * @PdfGenerator(
 *   id = "tcpdf",
 *   module = "pdf_api",
 *   title = @Translation("tcpdf"),
 *   description = @Translation("PDF generator using the TCPDF generator."),
 *   required_class = "TCPDF"
 * )
 */
class TcpdfGenerator extends PdfGeneratorBase implements ContainerFactoryPluginInterface {

  /**
   * Instance of the TCPDF class library.
   *
   * @var \TCPDF
   */
  protected $generator;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->generator = new TCPDF();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setter($pdf_content, $pdf_location, $save_pdf, $paper_orientation, $paper_size, $footer_content, $header_content, $path_to_binary = '') {
    $this->setPageOrientation($paper_orientation);
    $this->addPage($pdf_content);
    $this->setFooter("");
  }

  /**
   * {@inheritdoc}
   */
  public function getObject() {
    return $this->generator;
  }

  /**
   * {@inheritdoc}
   */
  public function setHeader($text) {
    $this->generator->SetPrintHeader($text);
  }

  /**
   * {@inheritdoc}
   */
  public function addPage($html) {
    $this->generator->AddPage();
    $this->generator->writeHTML($html);
  }

  /**
   * {@inheritdoc}
   */
  public function setPageOrientation($orientation = PdfGeneratorInterface::PORTRAIT) {
    if ($orientation == 'portrait') {
      $orientation = 'P';
    }
    else {
      $orientation = 'L';
    }
    $this->generator->setPageOrientation($orientation);
  }

  /**
   * {@inheritdoc}
   */
  public function setPageSize($page_size) {
    if ($this->isValidPageSize($page_size)) {
      $this->generator->AddPage("", $page_size, FALSE, TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setFooter($text) {
    $this->generator->writeHtmlCell("", 3, 20, 4, '<p>Page ' . $this->generator->getAliasNumPage() . ' of  ' . ' ' . $this->generator->getAliasNbPages() . '</p>', '', 1, 0, FALSE, 'R');
  }

  /**
   * {@inheritdoc}
   */
  public function save($location) {
    $this->generator->Output($location, 'F');
  }

  /**
   * {@inheritdoc}
   */
  public function send() {
    $this->generator->Output('htmlout.pdf', 'I');
  }

  /**
   * {@inheritdoc}
   */
  public function stream($filelocation) {
    $this->generator->Output($filelocation, 'D');
  }

}
