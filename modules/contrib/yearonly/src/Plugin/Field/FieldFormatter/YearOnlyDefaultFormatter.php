<?php

namespace Drupal\yearonly\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'yearonly_default' formatter.
 *
 * @FieldFormatter (
 * id = "yearonly_default",
 * label = @Translation("Year only"),
 * field_types = {
 * "yearonly"
 * }
 * )
 */
class YearOnlyDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#type' => 'markup',
        '#markup' => $item->value,
      ];
    }

    return $element;
  }

}
