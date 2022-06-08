<?php

namespace Drupal\yearonly\Plugin\Field\FieldType;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;

/**
 * Plugin implementation of the 'yearonly' field type.
 *
 * @FieldType(
 * id = "yearonly",
 * label = @Translation("Year only"),
 * description = @Translation("This field provide the ways to collect year only in provided date range."),
 * default_widget = "yearonly_default",
 * default_formatter = "yearonly_default",
 * )
 */
class YearOnlyItem extends FieldItemBase implements FieldItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'int',
          'length' => 50,
        ],
      ],
      'indexes' => [
        'value' => [
          'value',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];
    $properties['value'] = DataDefinition::create('integer');
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'yearonly_from' => '',
      'yearonly_to' => '',
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    $element['#markup'] = '<strong>' . $this->t('Select yearonly field range. This field will display the range as selected below on form.') . '</strong>';

    $options = array_combine(range(1900, date('Y') - 1), range(1900, date('Y') - 1));
    $element['yearonly_from'] = [
      '#type' => 'select',
      '#title' => $this->t('From'),
      '#default_value' => $this->getSetting('yearonly_from'),
      '#options' => $options,
      '#description' => $this->t('Select starting year.'),
      '#weight' => 1,
    ];

    $options = [
      'now' => 'NOW',
    ];
    $options += array_combine(range(date('Y') + 1, date('Y') + 50), range(date('Y') + 1, date('Y') + 50));
    $element['yearonly_to'] = [
      '#type' => 'select',
      '#title' => $this->t('To'),
      '#default_value' => $this->getSetting('yearonly_to'),
      '#options' => $options,
      '#description' => $this->t('Select last year.'),
      '#weight' => 1,
    ];

    return $element;
  }

}
