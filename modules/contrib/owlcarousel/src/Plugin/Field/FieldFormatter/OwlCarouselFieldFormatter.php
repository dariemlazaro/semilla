<?php

namespace Drupal\owlcarousel\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\owlcarousel\OwlCarouselGlobal;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'owl_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "owlcarousel_field_formatter",
 *   label = @Translation("OwlCarousel Carousel"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class OwlCarouselFieldFormatter extends EntityReferenceFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The image style storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $imageStyleStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, EntityStorageInterface $image_style_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->currentUser = $current_user;
    $this->imageStyleStorage = $image_style_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('image_style')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $owlcarousel_default_settings = OwlCarouselGlobal::defaultSettings();
    return $owlcarousel_default_settings + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $image_styles = image_style_options(FALSE);
    $description_link = Link::fromTextAndUrl(
      $this->t('Configure Image Styles'),
      Url::fromRoute('entity.image_style.collection')
    );
    $element['image_style'] = [
      '#title' => $this->t('Image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_style'),
      '#empty_option' => $this->t('None (original image)'),
      '#options' => $image_styles,
      '#description' => $description_link->toRenderable() + [
        '#access' => $this->currentUser->hasPermission('administer image styles'),
      ],
    ];

    // Link image to.
    $element['image_link'] = [
      '#title' => $this->t('Link image to'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_link'),
      '#empty_option' => $this->t('Nothing'),
      '#options' => [
        'content' => $this->t('Content'),
        'file' => $this->t('File'),
      ],
    ];

    // Items.
    $element['items'] = [
      '#type' => 'number',
      '#title' => $this->t('Items'),
      '#default_value' => !empty($this->getSetting('items')) ? $this->getSetting('items') : 3,
      '#description' => $this->t('Maximum amount of items displayed at a time with the widest browser width.'),
    ];

    // Margin.
    $element['margin'] = [
      '#type' => 'number',
      '#title' => $this->t('Margin'),
      '#default_value' => $this->getSetting('margin'),
      '#description' => $this->t('Margin from items.'),
    ];

    // Navigation.
    $element['nav'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Navigation'),
      '#default_value' => $this->getSetting('nav'),
      '#description' => $this->t('Display "next" and "prev" buttons.'),
    ];

    // Autoplay.
    $element['autoplay'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autoplay'),
      '#default_value' => $this->getSetting('autoplay'),
    ];

    // AutoplayHoverPause.
    $element['autoplayHoverPause'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Pause on hover'),
      '#default_value' => $this->getSetting('autoplayHoverPause'),
      '#description' => $this->t('Pause autoplay on mouse hover.'),
    ];

    // Dots.
    $element['dots'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Dots'),
      '#default_value' => $this->getSetting('dots'),
      '#description' => $this->t('Show dots.'),
    ];

    // DimensionMobile.
    $element['dimensionMobile'] = [
      '#type' => 'number',
      '#title' => $this->t('Mobile dimension'),
      '#default_value' => $this->getSetting('dimensionMobile'),
      '#description' => $this->t('Set the mobile dimensions in px.'),
    ];

    // ItemsMobile.
    $element['itemsMobile'] = [
      '#type' => 'number',
      '#title' => $this->t('Mobile items'),
      '#default_value' => $this->getSetting('itemsMobile'),
      '#description' => $this->t('Maximum amount of items displayed at mobile.'),
    ];

    // DimensionDesktop.
    $element['dimensionDesktop'] = [
      '#type' => 'number',
      '#title' => $this->t('Desktop dimension'),
      '#default_value' => $this->getSetting('dimensionDesktop'),
      '#description' => $this->t('Set the desktop dimensions in px.'),
    ];

    // itemsDesktop.
    $element['itemsDesktop'] = [
      '#type' => 'number',
      '#title' => $this->t('Desktop items'),
      '#default_value' => $this->getSetting('itemsDesktop'),
      '#description' => $this->t('Maximum amount of items displayed at desktop.'),
    ];

    return $element + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $itemsdisplay = $this->getSetting('items') ? $this->getSetting('items') : 3;
    $nav = $this->getSetting('nav') ? 'TRUE' : 'FALSE';
    $autoplay = $this->getSetting('autoplay') ? 'TRUE' : 'FALSE';
    $autoplaypause = $this->getSetting('autoplayHoverPause') ? 'TRUE' : 'FALSE';
    $dots = $this->getSetting('autoplayHoverPause') ? 'TRUE' : 'FALSE';

    $summary[] = $this->t('Owlcarousel Settings summary.');
    $summary[] = $this->t('Image style:') . $this->getSetting('image_style');
    $summary[] = $this->t('Link image to:') . $this->getSetting('image_link') ?? $this->t('Nothing');
    $summary[] = $this->t('Amount of items displayed:') . $itemsdisplay;
    $summary[] = $this->t('Margin from items:') . $this->getSetting('margin') . 'px';
    $summary[] = $this->t('Display next and prev buttons:') . $nav;
    $summary[] = $this->t('Autoplay:') . $autoplay;
    $summary[] = $this->t('Autoplay pause on mouse hover:') . $autoplaypause;
    $summary[] = $this->t('Show dots:') . $dots;

    if ($this->getSetting('dimensionMobile')) {
      $summary[] = $this->t('Mobile dimensions:') . $this->getSetting('dimensionMobile') . 'px';
    }

    if ($this->getSetting('itemsMobile')) {
      $summary[] = $this->t('Mobile items to show:') . $this->getSetting('itemsMobile');
    }

    if ($this->getSetting('dimensionDesktop')) {
      $summary[] = $this->t('Desktop dimensions:') . $this->getSetting('dimensionDesktop') . 'px';
    }

    if ($this->getSetting('itemsDesktop')) {
      $summary[] = $this->t('Desktop items to show:') . $this->getSetting('itemsDesktop');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $elements = [];

    $files = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }

    $url = NULL;
    $image_link_setting = $this->getSetting('image_link');
    // Check if the formatter involves a link.
    if ($image_link_setting == 'content') {
      $entity = $items->getEntity();
      if (!$entity->isNew()) {
        $url = $entity->toUrl()->toString();
      }
    }
    elseif ($image_link_setting == 'file') {
      $link_file = TRUE;
    }

    $image_style_setting = $this->getSetting('image_style');

    // Collect cache tags to be added for each item in the field.
    $cache_tags = [];
    if (!empty($image_style_setting)) {
      $image_style = $this->imageStyleStorage->load($image_style_setting);
      $cache_tags = $image_style->getCacheTags();
    }

    foreach ($files as $delta => $file) {
      if (isset($link_file)) {
        $image_uri = $file->getFileUri();
        $url = Url::fromUri(file_create_url($image_uri));
      }
      $cache_tags = Cache::mergeTags($cache_tags, $file->getCacheTags());

      // Extract field item attributes for the theme function, and unset them
      // from the $item so that the field template does not re-render them.
      $item = $file->_referringItem;
      $item_attributes = $item->_attributes;
      unset($item->_attributes);

      $elements[$delta] = [
        '#theme' => 'image_formatter',
        '#item' => $item,
        '#item_attributes' => $item_attributes,
        '#image_style' => $image_style_setting,
        '#url' => $url,
        '#cache' => [
          'tags' => $cache_tags,
        ],
      ];
    }
    $owlcarousel_default_settings = OwlCarouselGlobal::defaultSettings();
    $settings = $owlcarousel_default_settings;
    foreach ($settings as $k => $v) {
      $s = $this->getSetting($k);
      $settings[$k] = isset($s) ? $s : $settings[$k];
    }
    return [
      '#theme' => 'owlcarousel',
      '#items' => $elements,
      '#settings' => $settings,
      '#attached' => ['library' => ['owlcarousel/owlcarousel']],
    ];

  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return nl2br(Html::escape($item->value));
  }

}
