<?php

namespace Drupal\high_contrast\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class HighContrastConfigurationForm.
 *
 * This class provides the site-wide configuration for, for high contrast.
 */
class HighContrastConfigurationForm extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a HighContrastConfigurationForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler instance to use.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, FileSystemInterface $file_system) {
    parent::__construct($config_factory);

    $this->moduleHandler = $module_handler;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'high_contrast_configuration';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'high_contrast.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('high_contrast.settings');

    $form['colors'] = [
      '#type' => 'details',
      '#title' => $this->t('High contrast colors'),
      '#open' => TRUE,
      '#tree' => FALSE,
    ];

    $form['colors']['colors_background'] = [
      '#type' => 'color',
      '#title' => $this->t('Background'),
      '#default_value' => $config->get('colors_background'),
    ];

    $form['colors']['colors_text'] = [
      '#type' => 'color',
      '#title' => $this->t('Text'),
      '#default_value' => $config->get('colors_text'),
    ];

    $form['colors']['colors_hyperlinks'] = [
      '#type' => 'color',
      '#title' => $this->t('Hyperlinks'),
      '#default_value' => $config->get('colors_hyperlinks'),
    ];

    if ($this->moduleHandler->moduleExists('file')) {
      $form['logo'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('High contrast logo image settings'),
      ];

      $form['logo']['default_logo'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Use the default logo (file named logo-hg in your theme folder if it exists)'),
        '#default_value' => $config->get('default_logo'),
        '#tree' => FALSE,
        '#description' => $this->t('Check here if you want the theme to use the logo supplied with it.'),
      ];

      $form['logo']['settings'] = [
        '#type' => 'container',
        '#states' => [
          // Hide the logo settings when using the default logo.
          'invisible' => [
            'input[name="default_logo"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $form['logo']['settings']['logo_path'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Path to custom high contrast logo'),
        '#description' => $this->t('The path to the file you would like to use as your logo file instead of the default logo.'),
        '#default_value' => $config->get('logo_path'),
      ];

      $form['logo']['settings']['logo_upload'] = [
        '#type' => 'file',
        '#title' => $this->t('Upload high contrast logo image'),
        '#maxlength' => 40,
        '#description' => $this->t("If you don't have direct file access to the server, use this field to upload your logo."),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if ($this->moduleHandler->moduleExists('file')) {
      // Handle file uploads.
      $validators = ['file_validate_is_image' => []];

      // Check for a new uploaded logo.
      $file = file_save_upload('logo_upload', $validators, FALSE, 0);
      if (isset($file)) {
        // File upload was attempted.
        if ($file) {
          // Put the temporary file in form_values so we can save it on submit.
          $form_state->setValue('logo_upload', $file);
        }
        else {
          // File upload failed.
          $form_state->setErrorByName('logo_upload', $this->t('The logo could not be uploaded.'));
        }
      }

      // When intending to use the default logo, unset the logo_path.
      if ($form_state->getValue('default_logo')) {
        $form_state->unsetValue('logo_path');
      }

      // If the user provided a path for a logo, make sure a file exists at that
      // path.
      if ($form_state->getValue('logo_path')) {
        $path = $this->validatePath($form_state->getValue('logo_path'));
        if (!$path) {
          $form_state->setErrorByName('logo_path', $this->t('The custom logo path is invalid.'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('high_contrast.settings');

    $config->set('colors_background', $form_state->getValue('colors_background'));
    $config->set('colors_text', $form_state->getValue('colors_text'));
    $config->set('colors_hyperlinks', $form_state->getValue('colors_hyperlinks'));
    $config->set('default_logo', $form_state->getValue('default_logo'));
    $config->set('logo_path', $form_state->getValue('logo_path'));

    // If the user uploaded a new logo, save it to a permanent location and use
    // it in place of the default theme-provided file.
    if (!empty($form_state->getValue('logo_upload'))) {
      $source = $form_state->getValue('logo_upload')->getFileUri();
      $destination = file_build_uri($this->fileSystem->basename($source));
      $filename = $this->fileSystem->copy($source, $destination);
      $config->set('default_logo', 0);
      $config->set('logo_path', $filename);
    }

    $config->save();
  }

  /**
   * Helper function for the HighContrastConfigurationForm form.
   *
   * Attempts to validate normal system paths, paths relative to the public
   * files directory, or stream wrapper URIs. If the given path is any of the
   * above, returns a valid path or URI that the theme system can display.
   *
   * @param string $path
   *   A path relative to the Drupal root or to the public files directory, or
   *   a stream wrapper URI.
   *
   * @return mixed
   *   A valid path that can be displayed through the theme system, or FALSE if
   *   the path could not be validated.
   */
  protected function validatePath($path) {
    // Absolute local file paths are invalid.
    if ($this->fileSystem->realpath($path) == $path) {
      return FALSE;
    }
    // A path relative to the Drupal root or a fully qualified URI is valid.
    if (is_file($path)) {
      return $path;
    }
    // Prepend 'public://' for relative file paths within public filesystem.
    if (StreamWrapperManager::getScheme($path) === FALSE) {
      $path = 'public://' . $path;
    }
    if (is_file($path)) {
      return $path;
    }
    return FALSE;
  }

}
