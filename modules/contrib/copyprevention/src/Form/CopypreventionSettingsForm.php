<?php

namespace Drupal\copyprevention\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Copyprevention Settings Form class.
 */
class CopypreventionSettingsForm extends ConfigFormBase {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->getEditable('copyprevention.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
    // Load the service required to construct this class.
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'copyprevention_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['copyprevention.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $form['copyprevention_body'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Body tag attributes'),
      '#description' => $this->t('Apply these attributes to body tag.'),
      '#options' => [
        'selectstart' => $this->t('Disable text selection: onselectstart="return false;"'),
        'copy' => $this->t('Disable copy to clipboard: oncopy="return false;"'),
        'contextmenu' => $this->t('Disable right-click context menu: oncontextmenu="return false;"'),
      ],
      '#default_value' => $this->config->get('copyprevention_body'),
    ];
    $form['images'] = [
      '#type' => 'details',
      '#title' => $this->t('Images protection'),
      '#open' => TRUE,
    ];

    $form['images']['copyprevention_images'] = [
      '#type' => 'checkboxes',
      '#description' => $this->t('Apply these methods to images.'),
      '#options' => [
        'contextmenu' => $this->t('Disable right-click context menu on images: oncontextmenu="return false;"'),
        'transparentgif' => $this->t('Place transparent gif image above all images'),
      ],
      '#default_value' => $this->config->get('copyprevention_images'),
    ];
    $form['images']['copyprevention_images_min_dimension'] = [
      '#type' => 'select',
      '#title' => $this->t('Minimal image dimension'),
      '#description' => $this->t('Minimal image height or width to activate Copy Prevention.'),
      '#options' => [
        10 => 10,
        20 => 20,
        30 => 30,
        50 => 50,
        100 => 100,
        150 => 150,
        200 => 200,
        300 => 300,
        500 => 500,
      ],
      '#default_value' => $this->config->get('copyprevention_images_min_dimension'),
    ];

    $form['copyprevention_images_search'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Protect/hide images from search engines'),
      '#description' => $this->t('Select options to hide your images from showing up on image searches.'),
      '#options' => [
        'httpheader' => $this->t('Set "X-Robots-Tag: noimageindex" HTTP header'),
        'pagehead' => $this->t('Add "noimageindex" robots meta tag to page head'),
        'robotstxt' => $this->t('Disallow images (jpg, png, gif) indexing in robots.txt - requires <a href="@link" target="_blank">RobotsTxt</a> module', [
          '@link' => 'http://drupal.org/project/robotstxt',
        ]),
      ],
      '#default_value' => $this->config->get('copyprevention_images_search'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('copyprevention.settings');
    $config->set('copyprevention_body', $form_state->getValue('copyprevention_body'));
    $config->set('copyprevention_images', $form_state->getValue('copyprevention_images'));
    $config->set('copyprevention_images_min_dimension', $form_state->getValue('copyprevention_images_min_dimension'));
    $config->set('copyprevention_images_search', $form_state->getValue('copyprevention_images_search'));
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
