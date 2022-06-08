<?php

/**
 * @file
 * Hooks provided by the Photoswipe module.
 */

/**
 * Provides the ability to override Photoswipe settings passed to JS as Options.
 *
 * @param array $settings
 *   Default Photoswipe settings array.
 */
function hook_photoswipe_js_options_alter(array &$settings) {
  // Disable sharing links.
  $settings['shareEl'] = FALSE;

  // Change or translate share buttons:
  $settings['shareButtons'] = [
    [
      'id' => 'facebook',
      'label' => t('Share on Facebook'),
      'url' => 'https://www.facebook.com/sharer/sharer.php?u={{url}}',
    ],
    [
      'id' => 'twitter',
      'label' => t('Tweet'),
      'url' => 'https://twitter.com/intent/tweet?text={{text}}&url={{url}}',
    ],
    [
      'id' => 'pinterest',
      'label' => t('Pin it'),
      'url' => 'http://www.pinterest.com/pin/create/button/?url={{url}}&media={{image_url}}&description={{text}}',
    ],
    [
      'id' => 'download',
      'label' => t('Download image'),
      'url' => '{{raw_image_url}}',
      'download' => TRUE,
    ],
  ];
}
