<?php

namespace Drupal\printable\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\pdf_api\PdfGeneratorPluginManager;
use Drupal\printable\PrintableEntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides shared configuration form for all printable formats.
 */
class FormatConfigurationFormPdf extends FormBase {

  /**
   * The printable entity manager.
   *
   * @var \Drupal\printable\PrintableEntityManagerInterface
   */
  protected $printableEntityManager;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The PDF generator plugin manager.
   *
   * @var \Drupal\pdf_api\PdfGeneratorPluginManager
   */
  protected $pdfGeneratorPluginManager;

  /**
   * Constructs a new form object.
   *
   * @param \Drupal\printable\PrintableEntityManagerInterface $printable_entity_manager
   *   The printable entity manager.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   Defines the configuration object factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   *   The module handler service.
   */
  public function __construct(PrintableEntityManagerInterface $printable_entity_manager, ConfigFactory $configFactory, MessengerInterface $messenger, ModuleHandler $moduleHandler, PdfGeneratorPluginManager $pluginManager) {
    $this->printableEntityManager = $printable_entity_manager;
    $this->configFactory = $configFactory;
    $this->messenger = $messenger;
    $this->moduleHandler = $moduleHandler;
    $this->pdfGeneratorPluginManager = $pluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('printable.entity_manager'),
      $container->get('config.factory'),
      $container->get('messenger'),
      $container->get('module_handler'),
      $container->get('plugin.manager.pdf_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'printable_configuration_pdf';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $printable_format = NULL) {
    $plugins = $this->pdfGeneratorPluginManager->getDefinitions();
    foreach ($plugins as $id => $plugin) {
      if (!class_exists($plugin['required_class'])) {
        unset($plugins[$id]);
      }
    }
    if (!empty($plugins)) {
      $form['settings']['print_pdf_pdf_tool'] = [
        '#type' => 'radios',
        '#title' => $this->t('PDF generation tool'),
        '#options' => [],
        '#default_value' => $this->config('printable.settings')
          ->get('pdf_tool'),
        '#description' => $this->t('This option selects the PDF generation tool being used by this module to create the PDF version.'),
      ];
    }
    else {
      $this->messenger()
        ->addWarning($this->t('You are seeing no PDF generating tool because you have not installed any third party library using composer.'));
    }
    foreach ($plugins as $id => $plugin) {
      $form['settings']['print_pdf_pdf_tool']['#options'][$id] = $plugin['title'];
    }

    $form['settings']['print_pdf_content_disposition'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Save the pdf'),
      '#description' => $this->t('Save the pdf instead of showing inline'),
      '#default_value' => $this->config('printable.settings')->get('save_pdf'),
    ];
    $form['settings']['print_pdf_ignore_warnings'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ignore warnings'),
      '#description' => $this->t('Use the generated PDF even if warnings are indicated'),
      '#default_value' => $this->config('printable.settings')
        ->get('ignore_warnings'),
    ];
    $form['settings']['print_pdf_paper_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Paper size'),
      '#options' => [],
      '#default_value' => (string) $this->config('printable.settings')
        ->get('paper_size'),
      '#description' => $this->t('Choose the paper size of the generated PDF.'),
    ];
    $paper_sizes = [
      'A0',
      'A1',
      'A2',
      'A3',
      'A4',
      'A5',
      'A6',
      'A7',
      'A8',
      'A9',
      'B0',
      'B1',
      'B10',
      'B2',
      'B3',
      'B4',
      'B5',
      'B6',
      'B7',
      'B8',
      'B9',
      'C5E',
      'Comm10E',
      'DLE',
      'Executive',
      'Folio',
      'Ledger',
      'Legal',
      'Letter',
      'Tabloid',
    ];
    foreach ($paper_sizes as $sizes) {
      $form['settings']['print_pdf_paper_size']['#options'][$sizes] = $sizes;
    }
    $form['settings']['print_pdf_page_orientation'] = [
      '#type' => 'select',
      '#title' => $this->t('Page orientation'),
      '#options' => [
        'portrait' => $this->t('Portrait'),
        'landscape' => $this->t('Landscape'),
      ],
      '#default_value' => $this->config('printable.settings')
        ->get('page_orientation'),
      '#description' => $this->t('Choose the page orientation of the generated PDF.'),
    ];

    $token_help = '';
    $token_args = [];

    if ($this->moduleHandler->moduleExists('token')) {
      $build = [
        '#type' => 'container',
        'token_tree_link' => [
          '#theme' => 'token_tree_link',
          '#token_types' => ['all'],
          '#click_insert' => TRUE,
          '#dialog' => TRUE,
        ],
      ];
      $token_args = [
        '@browse_tokens_link' => \Drupal::service('renderer')->render($build),
      ];

      $token_help = ' This field supports tokens: @browse_tokens_link';

    }

    $form['settings']['print_pdf_filename'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PDF filename'),
      '#default_value' => $this->config('printable.settings')
        ->get('pdf_location'),
      '#description' => t("Filename with its location can be entered. If left empty and Save the pdf option has been selected the generated filename defaults to the node's path.The .pdf extension will be appended automatically." . $token_help, $token_args),
    ];
    $form['settings']['print_pdf_filename']['#element_validate'][] = 'token_element_validate';
    $form['settings']['print_pdf_filename'] += [
      '#token_types' => [
        'all',
      ],
    ];
    if (!empty($plugins['wkhtmltopdf'])) {
      $form['settings']['path_to_binary'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Path to binary file'),
        '#default_value' => $this->config('printable.settings')
          ->get('path_to_binary'),
        '#description' => $this->t("Enter the path to binary file for wkhtmltopdf over here."),
        '#states' => [
          'visible' => [
            'input[name="print_pdf_pdf_tool"]' => ['value' => 'wkhtmltopdf'],
          ],
        ],
      ];

      $form['settings']['print_pdf_use_xvfb_run'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Use Xvfb-run'),
        '#description' => $this->t('Enable this option if you get an error "QXcbConnection: Could not connect to display Aborted (core dumped)" when seeking to generate PDFs.'),
        '#default_value' => $this->config('printable.settings')
          ->get('print_pdf_use_xvfb_run'),
        '#states' => [
          'visible' => [
            'input[name="print_pdf_pdf_tool"]' => ['value' => 'wkhtmltopdf'],
          ],
        ],
      ];

      $form['settings']['path_to_xfb_run'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Path to Xvfb-run binary file'),
        '#default_value' => $this->config('printable.settings')
          ->get('path_to_xfb_run'),
        '#description' => $this->t("Enter the path to binary file for Xvfb-run over here."),
        '#states' => [
          'visible' => [
            'input[name="print_pdf_pdf_tool"]' => ['value' => 'wkhtmltopdf'],
            'input[name="print_pdf_use_xvfb_run"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }
    $form['settings']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Submit',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $pdf_tool = $this->config('printable.settings')->get('pdf_tool');
    $this->configFactory->getEditable('printable.settings')
      ->set('pdf_tool', $form_state->getValue('print_pdf_pdf_tool'))
      ->set('save_pdf', $form_state->getValue('print_pdf_content_disposition'))
      ->set('ignore_warnings', $form_state->getValue('print_pdf_ignore_warnings'))
      ->set('paper_size', (string) $form_state->getValue('print_pdf_paper_size'))
      ->set('page_orientation', $form_state->getValue('print_pdf_page_orientation'))
      ->set('pdf_location', $form_state->getValue('print_pdf_filename'))
      ->save();
    if (class_exists('mikehaertl\wkhtmlto\Pdf') && $pdf_tool == 'wkhtmltopdf') {
      $this->configFactory->getEditable('printable.settings')
        ->set('path_to_binary', $form_state->getValue('path_to_binary'))
        ->set('print_pdf_use_xvfb_run', $form_state->getValue('print_pdf_use_xvfb_run'))
        ->set('path_to_xfb_run', $form_state->getValue('path_to_xfb_run'))
        ->save();
    }
    $this->messenger()
      ->addStatus($this->t('The configuration option has been saved'));
  }

}
