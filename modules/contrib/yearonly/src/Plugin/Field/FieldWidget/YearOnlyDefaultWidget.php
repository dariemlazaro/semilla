<?php

namespace Drupal\yearonly\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;

/**
 * Plugin implementation of the 'yearonly_default' widget.
 *
 * @FieldWidget(
 * id = "yearonly_default",
 * label = @Translation("Select Year"),
 * field_types = {
 * "yearonly"
 * }
 * )
 */
class YearOnlyDefaultWidget extends WidgetBase implements WidgetInterface {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_settings = $this->getFieldSettings();
    if ($field_settings['yearonly_to'] == 'now') {
      $field_settings['yearonly_to'] = date('Y');
    }

    $options = array_combine(range($field_settings['yearonly_from'], $field_settings['yearonly_to']), range($field_settings['yearonly_from'], $field_settings['yearonly_to']));
    $element['value'] = $element + [
      '#type' => 'select',
      '#options' => $options,
      '#empty_value' => '',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : '',
      '#description' => $this->t('Select year'),
    ];
    return $element;
  }

}
