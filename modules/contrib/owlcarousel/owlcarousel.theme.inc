<?php

/**
 * @file
 * Theme.inc for functions.
 */

use Drupal\owlcarousel\OwlCarouselView;
use Drupal\Component\Serialization\Json;

/**
 * Prepares variables for views carousel template.
 *
 * Default template: views-bootstrap-carousel.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
 */
function template_preprocess_owlcarousel_views(array &$vars) {
  $view = $vars['view'];
  $handler = $vars['view']->style_plugin;
  $settings = $handler->options;

  $vars['id'] = OwlCarouselView::getUniqueId($view);
  $vars['attributes']['class'][] = 'owl-slider-wrapper';
  $vars['attributes']['class'][] = $vars['id'];
  $vars['attributes']['class'][] = 'owl-carousel';
  $vars['#attached']['library'][] = 'owlcarousel/owlcarousel';
  // Config set for owlcarousel.
  // This is then accessible in JS via drupalSettings.
  $vars['#attached']['drupalSettings']['owlcarousel_views'][$vars['id']] = Json::encode($settings);
}
