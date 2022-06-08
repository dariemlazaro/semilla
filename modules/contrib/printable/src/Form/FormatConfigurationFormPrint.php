<?php

namespace Drupal\printable\Form;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\printable\PrintableEntityManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactory;

/**
 * Provides shared configuration form for all printable formats.
 */
class FormatConfigurationFormPrint extends FormBase {

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
   * Constructs a new form object.
   *
   * @param \Drupal\printable\PrintableEntityManagerInterface $printable_entity_manager
   *   The printable entity manager.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   Defines the configuration object factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(PrintableEntityManagerInterface $printable_entity_manager, ConfigFactory $configFactory, MessengerInterface $messenger) {
    $this->printableEntityManager = $printable_entity_manager;
    $this->configFactory = $configFactory;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('printable.entity_manager'),
      $container->get('config.factory'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'printable_configuration_print';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $printable_format = NULL) {
    $form['settings']['print_html_sendtoprinter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send to printer'),
      '#default_value' => $this->config('printable.settings')
        ->get('send_to_printer'),
      '#description' => $this->t("Automatically calls the browser's print function when the printer-friendly version is displayed."),
    ];

    $form['settings']['print_html_windowclose'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Close window after sending to printer'),
      '#default_value' => $this->config('printable.settings')
        ->get('close_window'),
      '#description' => $this->t("When the above options are enabled, this option will close the window after its contents are printed."),
    ];

    $form['settings']['print_html_display_sys_urllist'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Printer-friendly URLs list in system pages'),
      '#default_value' => $this->config('printable.settings')->get('list_attribute'),
      '#description' => $this->t('Enabling this option will display a list of printer-friendly destination URLs at the bottom of the page.'),
    ];

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
    $this->configFactory->getEditable('printable.settings')
      ->set('send_to_printer', $form_state->getValue('print_html_sendtoprinter'))
      ->set('close_window', $form_state->getValue('print_html_windowclose'))
      ->set('list_attribute', $form_state->getValue('print_html_display_sys_urllist'))
      ->save();
    $this->messenger()->addStatus($this->t('The configuration option has been saved'));
  }

}
