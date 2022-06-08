<?php

namespace Drupal\jquery_countdown_timer\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use DateTime;

/**
 * Provides a "Jquery Countdown Timer" block.
 *
 * @Block(
 *   id = "jquery_countdown_timer",
 *   admin_label = @Translation("Countdown Timer")
 * )
 */
class JqueryCountdownTimerBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $font_size = 28;
    $dt = new DateTime('tomorrow');
    $countdown_datetime = $dt->format('Y-m-d H:i:s');

    return ['countdown_datetime' => $countdown_datetime, 'font_size' => $font_size];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['jquery_countdown_timer_date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Timer date'),
      '#required' => 1,
      '#default_value' => new DrupalDateTime($this->configuration['countdown_datetime']),
      '#date_date_element' => 'date',
      '#date_time_element' => 'time',
      '#date_year_range' => '2016:+50',
    ];
    $form['jquery_countdown_timer_font_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Timer font size'),
      '#default_value' => $this->configuration['font_size'],
      '#required' => 1,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $dt = $form_state->getValue('jquery_countdown_timer_date');
    $this->configuration['countdown_datetime'] = $dt->format('Y-m-d H:i:s');
    $this->configuration['font_size'] = $form_state->getValue('jquery_countdown_timer_font_size');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $settings = [
      'unixtimestamp' => strtotime($this->configuration['countdown_datetime']),
      'fontsize' => $this->configuration['font_size'],
    ];

    $build = [];
    $build['content'] = [
      '#markup' => '<div id="jquery-countdown-timer"></div><div id="jquery-countdown-timer-note"></div>',
    ];

    // Attach library containing css and js files.
    $build['#attached']['library'][] = 'jquery_countdown_timer/countdown.timer';
    $build['#attached']['drupalSettings']['countdown'] = $settings;

    return $build;
  }

}
