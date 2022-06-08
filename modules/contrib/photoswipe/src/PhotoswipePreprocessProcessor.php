<?php

namespace Drupal\photoswipe;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Preprocess photoswipe images.
 */
class PhotoswipePreprocessProcessor implements ContainerInjectionInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Token.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Image DTO.
   *
   * @var \Drupal\photoswipe\ImageDTO
   */
  protected $imageDTO;

  /**
   * Constructs new PhotoswipePreprocessProcessor object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity manager.
   * @param \Drupal\Core\Utility\Token $token
   *   Token.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   Language manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $channelFactory
   *   Chanel factory.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    Token $token,
    LanguageManagerInterface $languageManager,
    LoggerChannelFactoryInterface $channelFactory,
    RendererInterface $renderer
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->token = $token;
    $this->languageManager = $languageManager;
    $this->renderer = $renderer;
    $this->logger = $channelFactory->get('photoswipe');
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('token'),
      $container->get('language_manager'),
      $container->get('logger.factory'),
      $container->get('renderer')
    );
  }

  /**
   * Preprocess image.
   *
   * @param array $variables
   *   Variables.
   */
  public function preprocess(array &$variables) {
    $this->imageDTO = ImageDTO::createFromVariables($variables);
    $image = $this->getRandarableImage($variables);

    $variables['image'] = $image;
    $variables['path'] = $this->getPath();
    $variables['attributes']['class'][] = 'photoswipe';
    $variables['attributes']['data-size'] = $this->imageDTO->getWidth() . 'x' . $this->imageDTO->getHeight();
    $variables['attributes']['data-overlay-title'] = $this->getCaption();
    if (isset($image['#style_name']) && $image['#style_name'] === 'hide') {
      // Do not display if hidden is selected:
      $variables['attributes']['class'][] = 'hidden';
    }

  }

  /**
   * Set the caption.
   */
  protected function getCaption() {
    $settings = $this->imageDTO->getSettings();
    if (isset($settings['photoswipe_caption'])) {
      $caption_setting = $settings['photoswipe_caption'];
      switch ($caption_setting) {
        case 'alt':
          $caption = $this->imageDTO->getAlt();
          break;

        case 'title':
          $caption = $this->imageDTO->getTitle();
          break;

        // Backward compatibility for stored settings.
        case 'node_title':
        case 'entity_label':
          $caption = $this->imageDTO->getEntity()->label() ?: $this->imageDTO->getAlt();

        case 'custom':
          $entity_type = $this->imageDTO->getEntity()->getEntityTypeId();
          $caption = $this->token->replace($settings['photoswipe_caption_custom'],
            [
              $entity_type => $this->imageDTO->getEntity(),
              'file' => $this->imageDTO->getItem(),
            ],
            [
              'clear' => TRUE,
              'langcode' => $this->languageManager->getCurrentLanguage()->getId(),
            ]
          );
          break;

        default:
          // Assume the user wants to use another node field as the caption.
          $field_view['#view_mode'] = ($settings['photoswipe_view_mode']) ? $settings['photoswipe_view_mode'] : 'default';
          if (!isset($entity->{$caption_setting})) {
            // No such field exists we'd better warn and use something reliable.
            $id = $this->imageDTO->getEntity()->id();
            $msg = "'Photoswipe Caption' is unset for field view '@fv' on node: @nid.";
            $this->logger->warning($msg, [
              '@fv' => $field_view['#view_mode'],
              '@nid' => $id,
            ]);
            // Fallback to alt text:
            $caption = $this->imageDTO->getAlt();
            break;
          }
          $field_view = $entity->{$caption_setting}->view();
          $caption = \Drupal::service('renderer')->render($field_view);
          break;
      }
    }
    else {
      $caption = $this->imageDTO->getAlt();
    }
    return $caption;
  }

  /**
   * Build randarable array for given image.
   *
   * @param array $variables
   *   An associative array containing image variables.
   *
   * @return array
   *   Randarable array contains the image.
   */
  protected function getRandarableImage(array $variables) {
    $image = [
      '#theme' => 'image_style',
      '#uri' => $this->imageDTO->getUri(),
      '#alt' => $this->imageDTO->getAlt(),
      '#title' => $this->imageDTO->getTitle(),
      '#width' => $this->imageDTO->getWidth(),
      '#height' => $this->imageDTO->getHeight(),
      '#attributes' => $this->imageDTO->getItem()->_attributes,
      '#style_name' => $this->imageDTO->getSettings()['photoswipe_node_style'],
    ];

    if (isset($variables['delta']) && $variables['delta'] === 0 && !empty($this->imageDTO->getSettings()['photoswipe_node_style_first'])) {
      $image['#style_name'] = $this->imageDTO->getSettings()['photoswipe_node_style_first'];
    }

    // Render as a standard image if an image style is not given.
    if (empty($image['#style_name']) || $image['#style_name'] === 'hide') {
      $image['#theme'] = 'image';
    }

    return $image;
  }

  /**
   * Set image path.
   */
  protected function getPath() {
    $dimensions = $this->imageDTO->getDimensions();
    // Create the path to the image that will show in Photoswipe.
    if (($style_name = $this->imageDTO->getSettings()['photoswipe_image_style']) && !empty($dimensions)) {
      // Load the image style.
      $style = $this->entityTypeManager->getStorage('image_style')->load($style_name);

      // Set the dimensions.
      $style->transformDimensions($dimensions, $this->imageDTO->getUri());
      $this->imageDTO->setDimensions($dimensions);

      // Fetch the Image style path from the Image URI.
      return $style->buildUrl($this->imageDTO->getUri());
    }
    else {
      return \Drupal::service('file_url_generator')->generateAbsoluteString($this->imageDTO->getUri());
    }
  }

}
