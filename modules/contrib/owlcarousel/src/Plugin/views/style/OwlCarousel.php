<?php

namespace Drupal\owlcarousel\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render each item into owl carousel.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *     id = "owlcarousel",
 *     title = @Translation("OwlCarousel"),
 *     help = @Translation("Displays rows as OwlCarousel."),
 *     theme = "owlcarousel_views",
 *     display_types = {"normal"}
 * )
 */
class OwlCarousel extends StylePluginBase {

  /**
   * Does the style plugin allows to use style plugins.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Does the style plugin support custom css class for the rows.
   *
   * @var bool
   */
  protected $usesRowClass = TRUE;

  /**
   * Set default options.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $settings = _owlcarousel_default_settings();
    foreach ($settings as $k => $v) {
      $options[$k] = ['default' => $v];
    }
    return $options;
  }

  /**
   * Render the given style.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    // Items.
    $form['items'] = [
      '#type' => 'number',
      '#title' => $this->t('Items'),
      '#default_value' => $this->options['items'],
      '#description' => $this->t('Maximum amount of items displayed at a time with the widest browser width.'),
    ];
    // Margin.
    $form['margin'] = [
      '#type' => 'number',
      '#title' => $this->t('Margin'),
      '#default_value' => $this->options['margin'],
      '#description' => $this->t('Margin from items.'),
    ];
    // Navigation.
    $form['nav'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Navigation'),
      '#default_value' => $this->options['nav'],
      '#description' => $this->t('Display "next" and "prev" buttons.'),
    ];
    // Autoplay.
    $form['autoplay'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autoplay'),
      '#default_value' => $this->options['autoplay'],
    ];
    // AutoplayHoverPause.
    $form['autoplayHoverPause'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Pause on hover'),
      '#default_value' => $this->options['autoplayHoverPause'],
      '#description' => $this->t('Pause autoplay on mouse hover.'),
    ];
    // Dots.
    $form['dots'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Dots'),
      '#default_value' => $this->options['dots'],
      '#description' => $this->t('Show dots.'),
    ];
    // DimensionMobile.
    $form['dimensionMobile'] = [
      '#type' => 'number',
      '#title' => $this->t('Mobile dimension'),
      '#default_value' => $this->options['dimensionMobile'],
      '#description' => $this->t('Set the mobile dimensions in px.'),
    ];
    // ItemsMobile.
    $form['itemsMobile'] = [
      '#type' => 'number',
      '#title' => $this->t('Mobile items'),
      '#default_value' => $this->options['itemsMobile'],
      '#description' => $this->t('Maximum amount of items displayed at mobile.'),
    ];
    // DimensionDesktop.
    $form['dimensionDesktop'] = [
      '#type' => 'number',
      '#title' => $this->t('Desktop dimension'),
      '#default_value' => $this->options['dimensionDesktop'],
      '#description' => $this->t('Set the desktop dimensioni in px.'),
    ];
    // itemsDesktop.
    $form['itemsDesktop'] = [
      '#type' => 'number',
      '#title' => $this->t('Desktop items'),
      '#default_value' => $this->options['itemsDesktop'],
      '#description' => $this->t('Maximum amount of items displayed at desktop.'),
    ];
  }

}
