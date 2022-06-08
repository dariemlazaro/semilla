<?php

namespace Drupal\high_contrast\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\high_contrast\HighContrastTrait;

/**
 * Class HighContrastSwitchForm.
 *
 * This class provides the form for toggling high contrast on and off.
 */
class HighContrastSwitchForm extends FormBase {

  use HighContrastTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'high_contrast_switch';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Set cache context for the form.
    $form['#cache']['contexts'][] = 'high_contrast';

    // Default settings.
    $settings = [
      // Defines the widget type to use: 'links', 'select', 'radios'.
      'switcher_widget' => 'links',
      // Not used.
      'switcher_label' => 'Contrast:',
      // The label for the high contrast element.
      'high_label' => $this->t('Enable'),
      // Not used.
      'separator' => '|',
      // The label for the normal page form element.
      'normal_label' => $this->t('Disable'),
      // Whether or not the form should submit automatically.
      'use_ajax' => FALSE,
      // Shows a single link/checkbox instead or two links/radios.
      'toggle_element' => FALSE,
    ];

    // Merge in provided settings.
    foreach ($settings as $key => $value) {
      if (!empty($form_state->getBuildInfo()['args'][0][$key])) {
        $settings[$key] = $form_state->getBuildInfo()['args'][0][$key];
      }
    }

    // Provide possible values.
    $values = [0 => $settings['normal_label'], 1 => $settings['high_label']];

    // Build the select / radios form element.
    if ($settings['switcher_widget'] == 'select' || $settings['switcher_widget'] == 'radios') {

      $form['switch'] = [
        '#type' => $settings['switcher_widget'],
        '#options' => $values,
        '#default_value' => $this->highContrastEnabled() ? 1 : 0,
      ];

      // If a toggle option is preferred, show a single checkbox instead.
      if ($settings['switcher_widget'] == 'radios' && $settings['toggle_element']) {
        $form['switch']['#type'] = 'checkbox';
        $form['switch']['#title'] = $settings['high_label'];
        unset($form['switch']['#options']);
      }

      $form['submit'] = [
        '#type' => 'submit',
        '#value' => 'Go',
      ];

      // The form should automatically submit. Hide the submit button as well.
      if ($settings['use_ajax']) {
        $form['switch']['#attributes'] = ['onChange' => 'this.form.submit();'];
        $form['submit']['#attributes']['class'][] = 'js-hide';
      }
    }
    // Or build the switch links.
    else {
      // Fetch current url for the redirect back.
      $current_url = Url::fromRoute('<current>')->toString();

      // No toggle, so build 2 links.
      if (!$settings['toggle_element']) {
        $form['enable_link'] = [
          '#type' => 'link',
          '#title' => $settings['high_label'],
          '#url' => Url::fromRoute('high_contrast.enable', [], ['query' => ['destination' => $current_url]]),
        ];

        $form['disable_link'] = [
          '#type' => 'link',
          '#title' => $settings['normal_label'],
          '#url' => Url::fromRoute('high_contrast.disable', [], ['query' => ['destination' => $current_url]]),
        ];
      }
      // Or build one toggle link.
      else {
        $route = $this->highContrastEnabled() ? 'high_contrast.disable' : 'high_contrast.enable';

        $form['toggle_link'] = [
          '#type' => 'link',
          '#title' => $this->highContrastEnabled() ? $settings['normal_label'] : $settings['high_label'],
          '#url' => Url::fromRoute($route, [], ['query' => ['destination' => $current_url]]),
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Handles the saving of the high contrast state from select and radios. The
   * links are handled via a route.
   *
   * @see HighContrastController
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('switch') == 1) {
      $this->enableHighContrast();
    }
    else {
      $this->disableHighContrast();
    }
  }

}
