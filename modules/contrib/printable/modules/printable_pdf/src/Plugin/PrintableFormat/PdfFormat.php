<?php

namespace Drupal\printable_pdf\Plugin\PrintableFormat;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\Url;
use Drupal\pdf_api\PdfGeneratorPluginManager;
use Drupal\printable\LinkExtractor\LinkExtractorInterface;
use Drupal\printable\Plugin\PrintableFormatBase;
use Drupal\printable\PrintableCssIncludeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a plugin to display a PDF version of a page.
 *
 * @PrintableFormat(
 *   id = "pdf",
 *   module = "printable_pdf",
 *   title = @Translation("PDF"),
 *   description = @Translation("PDF description.")
 * )
 */
class PdfFormat extends PrintableFormatBase {

  /**
   * The PDF generator plugin manager service.
   *
   * @var \Drupal\pdf_api\PdfGeneratorPluginManager
   */
  protected $pdfGeneratorManager;

  /**
   * The PDF generator plugin instance.
   *
   * @var \Drupal\pdf_api\Plugin\PdfGeneratorInterface
   */
  protected $pdfGenerator;

  /**
   * The filename to use.
   *
   * @var string
   */
  protected $filename;

  /**
   * The path cerrent service.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $pathCurrent;

  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The public stream wrapper service.
   *
   * @var \Drupal\Core\StreamWrapper\PublicStream
   */
  protected $publicStream;

  /**
   * The Drupal messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   *
   * @param array $configuration
   *   The configuration array.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory service.
   * @param \Drupal\pdf_api\PdfGeneratorPluginManager $pdf_generator_manager
   *   The PDF generator plugin manager service.
   * @param \Drupal\printable\PrintableCssIncludeInterface $printable_css_include
   *   The printable CSS include interface.
   * @param \Drupal\printable\LinkExtractor\LinkExtractorInterface $link_extractor
   *   The Link extractor service.
   * @param \Drupal\Core\Path\CurrentPathStack $pathCurrent
   *   Represents the current path for the current request.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   Request stack that controls the lifecycle of requests.
   * @param \Drupal\Core\StreamWrapper\PublicStream $public_stream
   *   The public stream wrapper service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The Drupal messenger service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ConfigFactory $config_factory, PdfGeneratorPluginManager $pdf_generator_manager, PrintableCssIncludeInterface $printable_css_include, LinkExtractorInterface $link_extractor, CurrentPathStack $pathCurrent, RequestStack $requestStack, PublicStream $public_stream, MessengerInterface $messenger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $config_factory, $printable_css_include, $link_extractor);
    $this->pdfGeneratorManager = $pdf_generator_manager;
    $this->pathCurrent = $pathCurrent;
    $this->requestStack = $requestStack;
    $this->publicStream = $public_stream;
    $this->messenger = $messenger;
    $pdf_library = (string) $this->configFactory->get('printable.settings')->get('pdf_tool');

    if (!$pdf_library) {
      $this->messenger->addError($this->t('A PDF generation toolkit needs to be selected. Please visit the %link.', [
        '%link' => Link::fromTextAndUrl('admin interface', Url::fromRoute('printable.format_configure_pdf'))->toString(),
      ]));
      throw new NotFoundHttpException();
    }

    $pdf_library = strtolower($pdf_library);
    try {
      $this->pdfGenerator = $this->pdfGeneratorManager->createInstance($pdf_library);
    }
    catch (\Exception $e) {
      // The thrower is assumed to have already logged an error but not
      // displayed a message.
      $this->messenger()->addError('PDF generation is not working at the moment. We apologise for the inconvenience.');
      throw $e;
    }

    if ($pdf_library != 'wkhtmltopdf') {
      return;
    }

    $options = [
      'enable-local-file-access',
    ];
    $use_xvfb_run = (string) $this->configFactory->get('printable.settings')->get('print_pdf_use_xvfb_run');
    $path_to_xfb_run = (string) $this->configFactory->get('printable.settings')->get('path_to_xfb_run');
    $ignore_warnings = (bool) $this->configFactory->get('printable.settings')->get('ignore_warnings');
    if ($use_xvfb_run) {
      $options = [
        'use-xserver' => NULL,
        'commandOptions' => [
          'enableXvfb' => TRUE,
          'xvfbRunBinary' => $path_to_xfb_run,
        ],
        'ignoreWarnings' => $ignore_warnings,
        'enable-local-file-access' => NULL,
      ];
    }
    $this->pdfGenerator->getObject()->setOptions($options);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('config.factory'),
      $container->get('plugin.manager.pdf_generator'),
      $container->get('printable.css_include'),
      $container->get('printable.link_extractor'),
      $container->get('path.current'),
      $container->get('request_stack'),
      $container->get('stream_wrapper.public'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'pdf_generator' => 'wkhtmltopdf',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(){}

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();
    $options = [];
    foreach ($this->pdfGeneratorManager->getDefinitions() as $definition) {
      $options[$definition['id']] = $definition['title'];
    }
    $form['pdf_generator'] = [
      '#type' => 'radios',
      '#title' => 'PDF Generator',
      '#default_value' => $config['pdf_generator'],
      '#options' => $options,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration([
      'pdf_generator' => $form_state['values']['pdf_generator'],
    ]);
  }

  /**
   * Get  the header content.
   *
   * @return string
   *   Content of header.
   */
  public function getHeaderContent() {
    $pdf_header = [
      '#theme' => 'printable_pdf_header',
    ];
    return render($pdf_header);
  }

  /**
   * Get  the footer content.
   *
   * @return string
   *   Content of footer.
   */
  public function getFooterContent() {
    $pdf_footer = [
      '#theme' => 'printable_pdf_footer',
    ];
    return render($pdf_footer);
  }

  /**
   * Get the HTML content for PDF generation.
   *
   * @return string
   *   HTML content for PDF.
   */
  public function buildPdfContent() {
    $content = parent::buildContent();
    $content = render($content);

    $content = preg_replace(['#printable://#', '/\?itok=.*"/'], ['', '"'], $content);
    $rendered_page = parent::extractLinks($content);
    return $rendered_page;
  }

  /**
   * Set formatted header and footer.
   */
  public function formattedHeaderFooter() {
    // And this can be used by users who do not want default one, this example
    // is for wkhtmltopdf generator.
    $this->pdfGenerator->getObject()->SetFooter('This is a footer on left side||' . 'This is a footer on right side');
  }

  /**
   * Return default headers (may be overridden by the generator).
   *
   * @param string $filename
   *   The path to generated file that is sent to the browser.
   * @param bool $download
   *   Whether to download the PDF or display it in the browser.
   *
   * @return array
   *   Default headers for the response object.
   */
  private function getHeaders($filename, $download) {
    $disposition = $download ? 'attachment' : 'inline';
    return [
      'Content-Type'              => Unicode::mimeHeaderEncode('application/pdf'),
      'Content-Disposition'       => $disposition . '; filename="' . basename($filename) . '"',
      'Content-Length'            => filesize($filename),
      'Content-Transfer-Encoding' => 'binary',
      'Pragma'                    => 'no-cache',
      'Cache-Control'             => 'must-revalidate, post-check=0, pre-check=0',
      'Expires'                   => '0',
      'Accept-Ranges'             => 'bytes',
    ];
  }

  /**
   * File send callback for the Streamed response.
   */
  public function streamResponseContent() {
    $this->pdfGenerator->send();
  }

  /**
   * Remove tokens from images so we just get the path.
   */
  public function removeImageTokens($subject) {
    // We only need to do this for absolute paths, not external images, so
    // search for mentions of DRUPAL_ROOT.
    $next_pos = strpos($subject, DRUPAL_ROOT);
    while ($next_pos !== FALSE) {
      $have_matches = preg_match('/[\s\'"]/', substr($subject, $next_pos), $matches, PREG_OFFSET_CAPTURE);
      $path_end = $have_matches ? $matches[0][1] : strlen($subject) - $next_pos + 1;
      $query_start = strpos(substr($subject, $next_pos, $path_end), '?');
      if ($query_start !== FALSE) {
        $subject = substr($subject, 0, $next_pos + $query_start) . substr($subject, $next_pos + $path_end);
      }
      $next_pos = strpos($subject, DRUPAL_ROOT, $next_pos + $path_end - $query_start + 1);
    }
    return $subject;
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse() {
    $paper_size = (string) $this->configFactory->get('printable.settings')->get('paper_size');
    $paper_orientation = $this->configFactory->get('printable.settings')->get('page_orientation');
    $path_to_binary = $this->configFactory->get('printable.settings')->get('path_to_binary');
    $save_pdf = $this->configFactory->get('printable.settings')->get('save_pdf');
    $pdf_location = $this->configFactory->get('printable.settings')->get('pdf_location');

    if ($this->pdfGenerator->usePrintableDisplay()) {
      $raw_content = $this->buildPdfContent();

      $basepath = \Drupal::request()->getBasePath();
      if ($basepath) {
        $raw_content = str_replace('src="' . $basepath, 'src=', $raw_content);
      }

      $pdf_content = $this->removeImageTokens($raw_content);
    }
    else {
      $pdf_content = null;
      $this->pdfGenerator->setEntity($this->entity);
    }

    $footer_content = $this->getFooterContent();
    $header_content = $this->getHeaderContent();
    // $this->formattedHeaderFooter();
    $this->pdfGenerator->setter($pdf_content, $pdf_location, $save_pdf, $paper_orientation, $paper_size, $footer_content, $header_content, $path_to_binary);

    if (empty($pdf_location)) {
      $pdf_location = str_replace("/", "_", $this->pathCurrent->getPath()) . '.pdf';
      $public_dir = $this->publicStream->getDirectoryPath();
      $pdf_location = trim($public_dir, '/') . '/' . substr($pdf_location, 1);
    }
    else {
      // @todo A token per source entity type? Seems overkill so I'll wait
      // until requested.
      $token_service = \Drupal::token();
      $url_parts = explode('/', $this->pathCurrent->getPath());
      $entity = \Drupal::routeMatch()->getParameter('entity');
      $entity_type = count($url_parts) > 2 ? $url_parts[1] : NULL;
      $pdf_location = $token_service->replace($pdf_location, [$entity_type => $entity]);
    }

    $this->filename = DRUPAL_ROOT . '/' . $pdf_location;

    $this->pdfGenerator->save($this->filename);
    if ($this->pdfGenerator->displayErrors()) {
      $source_url = $this->requestStack->getCurrentRequest()->getRequestUri();
      $pos = strpos($source_url, "printable");
      $source_url = substr($source_url, 0, $pos - 1);
      return new RedirectResponse($source_url);
    }

    return (new BinaryFileResponse($this->filename, 200, $this->getHeaders($pdf_location, $save_pdf)))->deleteFileAfterSend(TRUE);
  }

}
