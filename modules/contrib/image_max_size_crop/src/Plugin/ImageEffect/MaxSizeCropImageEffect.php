<?php

namespace Drupal\image_max_size_crop\Plugin\ImageEffect;

use Drupal\Core\Image\ImageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\ImageEffect\CropImageEffect;

/**
 * Crop image with maximum sizes.
 *
 * Crop an image resource with respect for maximum size, and with only one
 * dimension required.
 *
 * @ImageEffect(
 *   id = "image_max_size_crop",
 *   label = @Translation("Maximum size crop"),
 *   description = @Translation("Cropping will remove portions of an image to make it the specified dimensions. This style only resizes when the image dimension(s) is larger than the specified dimension(s).")
 * )
 */
class MaxSizeCropImageEffect extends CropImageEffect {

  /**
   * {@inheritdoc}
   */
  public function transformDimensions(array &$dimensions, $uri) {
    // The new image will have the exact dimensions defined for the effect where specified, and the original dimension otherwise
    if (!empty($this->configuration['width'])) {
      $dimensions['width'] = $this->configuration['width'];
    }
    if (!empty($this->configuration['height'])) {
      $dimensions['height'] = $this->configuration['height'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {

    // Set the desired dimensions, if the width is empty or not high enough.
    $original_width = $this->configuration['width'];
    if (!$this->configuration['width'] || $this->configuration['width'] > $image->getWidth()) {
      $this->configuration['width'] = $image->getWidth();
    }

    // Set the desired dimensions, if the height is empty or not high enough.
    $original_height = $this->configuration['height'];
    if (!$this->configuration['height'] || $this->configuration['height'] > $image->getHeight()) {
      $this->configuration['height'] = $image->getHeight();
    }

    $result = parent::applyEffect($image);

    // Restore configuration so that settings screen is shown correctly.
    $this->configuration['width'] = $original_width;
    $this->configuration['height'] = $original_height;

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    unset($form['width']['#required']);
    unset($form['height']['#required']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    if ($form_state->isValueEmpty('width') && $form_state->isValueEmpty('height')) {
      $form_state->setErrorByName('data', $this->t('Width and height can not both be blank.'));
    }
  }

}
