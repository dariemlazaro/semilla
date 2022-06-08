<?php

namespace Drupal\printable\Form;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\printable\PrintableEntityManagerInterface;
use Drupal\printable\PrintableFormatPluginManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactory;

/**
 * Provides shared configuration form for all printable formats.
 */
class LinksConfigurationForm extends FormBase {

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
   * @param \Drupal\printable\PrintableFormatPluginManager $printable_format_manager
   *   The printable format plugin manager.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   Defines the configuration object factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(PrintableEntityManagerInterface $printable_entity_manager, PrintableFormatPluginManager $printable_format_manager, ConfigFactory $configFactory, MessengerInterface $messenger) {
    $this->printableEntityManager = $printable_entity_manager;
    $this->printableFormatManager = $printable_format_manager;
    $this->configFactory = $configFactory;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('printable.entity_manager'),
      $container->get('printable.format_plugin_manager'),
      $container->get('config.factory'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'links_configuration';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $printable_format = NULL) {

    $form['settings']['print_print_link_pos'] = [
      '#type' => 'checkboxes',
      '#title' => 'Link location',
      '#default_value' => [],
      '#options' => [
        'node' => $this->t('Links area'),
        'comment' => $this->t('Comment area'),
        'user' => $this->t('User area'),
      ],
      '#description' => $this->t('Choose the location of the link(s) to the printer-friendly version pages. The Links area is usually below the node content, whereas the Comment area is placed near the comments. The user area is near the user name. Select the options for which you want to enable the link. If you select any option then it means that you have enabled printable support for that entity in the configuration tab.'),
    ];
    foreach ($this->config('printable.settings')->get('printable_print_link_locations') as $link_location) {
      $form['settings']['print_print_link_pos']['#default_value'][] = $link_location;
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
    $this->configFactory->getEditable('printable.settings')
      ->set('printable_print_link_locations', $form_state->getValue('print_print_link_pos'))
      ->save();
    $this->messenger()->addStatus($this->t('The configuration option has been saved'));
  }

}
