<?php

declare(strict_types = 1);

namespace Drupal\animated_gif\Plugin\Field\FieldFormatter;

use Drupal\animated_gif\AnimatedGifUtility;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageUrlFormatter;

/**
 * Plugin implementation of the 'image_url' formatter for animated_gif.
 *
 * @FieldFormatter(
 *     id = "animated_gif_image_url",
 *     label = @Translation("Animated GIF URL to image"),
 *     field_types = {
 *         "image"
 *     }
 * )
 */
class AnimatedGifImageUrlFormatter extends ImageUrlFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $items */
    $images = $this->getEntitiesToView($items, $langcode);
    if (empty($images)) {
      // Early opt-out if the field is empty.
      return $elements;
    }

    /** @var \Drupal\file\FileInterface[] $images */
    foreach ($images as $delta => $image) {
      $image_uri = $image->getFileUri();

      if ($image->getMimeType() === 'image/gif' && AnimatedGifUtility::isAnAnimatedGif($image_uri)) {
        // No image style is wanted for animated gifs.
        $url = $this->fileUrlGenerator->transformRelative($this->fileUrlGenerator->generateString($image_uri));
        $elements[$delta]['#markup'] = $url;
      }
    }
    return $elements;
  }

}
