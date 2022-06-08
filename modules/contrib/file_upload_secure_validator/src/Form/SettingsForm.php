<?php

namespace Drupal\file_upload_secure_validator\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Encoder\CsvEncoder;

/**
 * A configuration settings form.
 *
 * This form is used by administrators to configure options such as the MIME
 * types equivalence groups.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The internally used delimiter for encoding to CSV.
   *
   * @var string
   */
  const CSV_DELIMITER = ',';

  /**
   * ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * FileUploadSecureValidator definition.
   *
   * @var \Drupal\file_upload_secure_validator\Service\FileUploadSecureValidator
   */
  protected $fileUploadSecureValidator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->configFactory = $container->get('config.factory');
    $instance->fileUploadSecureValidator = $container->get('file_upload_secure_validator');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'file_upload_secure_validator.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'file-upload-secure-validator-settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['mime_types_equivalence_groups'] = [
      '#type' => 'textarea',
      '#title' => $this->t('MIME types equivalence group(s)'),
      '#description' => $this->t('A CSV-like list of MIME types groups; if two MIME types are part of the same group, then, File Upload Secure Validator will not protest.'),
      '#default_value' => $this->getConfigurationAsCsvString(),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Provides backward compatibility.
    $options = [];
    if (defined('CsvEncoder::NO_HEADERS_KEY')) {
      $options[CsvEncoder::NO_HEADERS_KEY] = TRUE;
    }

    $mimeTypesGroups = (new CsvEncoder())
      ->decode($form_state->getValue('mime_types_equivalence_groups'), 'csv', $options);

    $this->config('file_upload_secure_validator.settings')
      ->set('mime_types_equivalence_groups', $mimeTypesGroups)
      ->save();
  }

  /**
   * Returns a CSV representation of the configuration stored in the database.
   */
  private function getConfigurationAsCsvString() {
    $configuration = $this->config('file_upload_secure_validator.settings')->get('mime_types_equivalence_groups');

    // Provides backward compatibility.
    $options = [
      CsvEncoder::DELIMITER_KEY => ',',
    ];
    if (defined('CsvEncoder::NO_HEADERS_KEY')) {
      $options[CsvEncoder::NO_HEADERS_KEY] = TRUE;
    }

    $csvString = (new CsvEncoder())->encode($configuration, CsvEncoder::FORMAT, $options);
    return preg_replace('#' . self::CSV_DELIMITER . '+$#m', '', $csvString);
  }

}
