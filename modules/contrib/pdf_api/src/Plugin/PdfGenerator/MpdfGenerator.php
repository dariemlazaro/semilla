<?php

/**
 * @file
 * Contains \Drupal\pdf_api\Plugin\MpdfGenerator.
 */

namespace Drupal\pdf_api\Plugin\PdfGenerator;

use Drupal\Component\Utility\Random;
use Drupal\pdf_api\Plugin\PdfGeneratorBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\pdf_api\Annotation\PdfGenerator;
use Drupal\Core\Annotation\Translation;
use Drupal\pdf_api\Plugin\PdfGeneratorInterface;
use Mpdf\Mpdf;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * A PDF generator plugin for the mPDF library.
 *
 * @PdfGenerator(
 *   id = "mpdf",
 *   module = "pdf_api",
 *   title = @Translation("mPDF"),
 *   description = @Translation("PDF generator using the mPDF generator."),
 *   required_class = "Mpdf\Mpdf"
 * )
 */
class MpdfGenerator extends PdfGeneratorBase implements ContainerFactoryPluginInterface {

  /**
   * Instance of the mPdf class library.
   *
   * @var \mPdf
   */
  protected $generator;

  /**
   * The saved header content.
   *
   * @var string
   */
  protected $headerContent;

  /**
   * The saved PDF content.
   *
   * @var string
   */
  protected $pdfContent;

  /**
   * The saved footer content.
   *
   * @var string
   */
  protected $footerContent;

  /**
   * The temporary directory we created.
   *
   * @var string
   */
  protected $tmpDir;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
    $this->setPageSize($paper_size);
    $this->setPageOrientation($paper_orientation);

    // Save until the generator is constructed in the preGenerate method.
    $this->headerContent = $header_content;
    $this->pdfContent = $pdf_content;
    $this->footerContent = $footer_content;
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
    $this->generator->SetHeader($text);
  }

  /**
   * {@inheritdoc}
   */
  public function addPage($html) {
    $this->generator->addPage($html);
  }

  /**
   * {@inheritdoc}
   */
  public function setPageOrientation($orientation = PdfGeneratorInterface::PORTRAIT) {
    if ($orientation == PdfGeneratorInterface::PORTRAIT) {
      $orientation = 'P';
    }
    else {
      $orientation = 'L';
    }
    $this->setOptions(array('orientation' => $orientation));
  }

  /**
   * {@inheritdoc}
   */
  public function setPageSize($page_size) {
    if ($this->isValidPageSize($page_size)) {
      $this->setOptions(array('sheet-size' => $page_size));
    }
  }

  /**
   * Sets the password in PDF.
   *
   * @param string $password
   *   The password which will be used in PDF.
   */
  public function setPassword($password) {
    if (isset($password) && $password != NULL) {
      // Print and Copy is allowed.
      $this->generator->SetProtection(array('print', 'copy'), $password, $password);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setFooter($text) {
     $this->generator->SetFooter($text);
  }

  /**
   * {@inheritdoc}
   */
  public function save($location) {
    $this->preGenerate();
    $this->generator->Output($location, 'F');
    $this->postGenerate();
  }

  /**
   * {@inheritdoc}
   */
  public function send() {
    $this->generator->Output("", "I");
  }

  /**
   * {@inheritdoc}
   */
  public function stream($filelocation) {
    $this->generator->Output($filelocation, 'F');
  }

  /**
   * Set the global options from the plugin into the mPDF generator class.
   */
  protected function preGenerate() {
    /*
     * We have to pass the initial page size and orientation that we want to
     * the constructor, so we delay making the generator until we have those
     * details.
     *
     * mPDF is also strange in its handling of parameters. We can't just set
     * the page size and orientation separately (as you'd expect) but need to
     * combine them in the format argument for them to be effective from the
     * get-go.
     */
    $options = $this->options;

    $orientation = $options['orientation'] ? $options['orientation'] : 'P';

    $filesystem = \Drupal::service('file_system');
    $random = new Random();
    $this->tmpDir = $filesystem->getTempDirectory() . '/' . $random->name(16, TRUE);
    $filesystem->mkdir($this->tmpDir);

    $config = [
      'format' => $this->isValidPageSize($options['sheet-size']) ? $options['sheet-size'] : 'A4',
      'tempDir' => $this->tmpDir,
    ];

    if ($orientation == 'L') {
      $config['format'] .= '-' . $orientation;
    }

    $this->generator = new mPDF($config);

    // Apply any other options.
    unset($options['orientation']);
    unset($options['sheet-size']);

    $this->generator->AddPageByArray($options);

    $this->setHeader($this->headerContent);
    $this->setFooter($this->footerContent);
    $stylesheet = '.node_view  { display: none; }';
    $this->generator->WriteHTML($stylesheet, 1);
    $this->generator->WriteHTML(utf8_encode($this->pdfContent), 0);
  }

  /**
   * Post generation cleanup.
   */
  protected function postGenerate() {
    $filesystem = \Drupal::service('file_system');
    $filesystem->deleteRecursive($this->tmpDir);
  }

}
