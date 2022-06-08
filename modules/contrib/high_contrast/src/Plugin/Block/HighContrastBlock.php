<?php

namespace Drupal\high_contrast\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a high contrast Block.
 *
 * @Block(
 *   id = "high_contrast",
 *   admin_label = @Translation("High contrast"),
 * )
 */
class HighContrastBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    $form = \Drupal::formBuilder()->getForm('Drupal\high_contrast\Form\HighContrastSwitchForm', $config);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $widgets_types = [
      'links' => $this->t('Links'),
      'select' => $this->t('Select list'),
      'radios' => $this->t('Radio buttons'),
    ];

    $form['switcher_widget'] = [
      '#type' => 'select',
      '#title' => $this->t('Switcher widget'),
      '#default_value' => isset($config['switcher_widget']) ? $config['switcher_widget'] : '',
      '#options' => $widgets_types,
      '#required' => TRUE,
    ];

    $form['high_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('High contrast label'),
      '#default_value' => isset($config['high_label']) ? $config['high_label'] : $this->t('Enable'),
      '#required' => TRUE,
    ];

    $form['normal_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Normal contrast label'),
      '#default_value' => isset($config['normal_label']) ? $config['normal_label'] : $this->t('Disable'),
      '#required' => TRUE,
    ];

    $form['use_ajax'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use AJAX to submit automatically.'),
      '#default_value' => isset($config['use_ajax']) ? $config['use_ajax'] : FALSE,
    ];

    $form['toggle_element'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Toggle switch'),
      '#description' => $this->t('Shows a single link/checkbox instead or two links/radios.'),
      '#default_value' => isset($config['toggle_element']) ? $config['toggle_element'] : FALSE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['switcher_widget'] = $form_state->getValue('switcher_widget');
    $this->configuration['high_label'] = $form_state->getValue('high_label');
    $this->configuration['normal_label'] = $form_state->getValue('normal_label');
    $this->configuration['use_ajax'] = $form_state->getValue('use_ajax');
    $this->configuration['toggle_element'] = $form_state->getValue('toggle_element');
  }

}
