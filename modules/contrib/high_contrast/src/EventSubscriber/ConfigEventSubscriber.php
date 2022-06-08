<?php

namespace Drupal\high_contrast\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * A subscriber for updating the stylesheet when the configuration is updated.
 */
class ConfigEventSubscriber implements EventSubscriberInterface {

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Config factory interface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * Constructs a ConfigEventSubscriber  object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(FileSystemInterface $file_system, ConfigFactoryInterface $config_factory) {
    $this->fileSystem = $file_system;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = ['updateStylesheet'];
    return $events;
  }

  /**
   * Regenerate the stylesheet.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The Event to process.
   */
  public function updateStylesheet(ConfigCrudEvent $event) {
    // Check if the save came from the high contrast configuration.
    if ($event->getConfig()->getName() === 'high_contrast.settings') {
      $dir = HIGH_CONTRAST_CSS_FOLDER;
      $file = HIGH_CONTRAST_CSS_LOCATION;
      $this->fileSystem->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY);

      $config = $this->configFactory->get('high_contrast.settings');
      $css = _high_contrast_build_css($config->get('colors_background'), $config->get('colors_text'), $config->get('colors_hyperlinks'));
      $this->fileSystem->saveData($css, $file, FileSystemInterface::EXISTS_REPLACE);
    }
  }

}
