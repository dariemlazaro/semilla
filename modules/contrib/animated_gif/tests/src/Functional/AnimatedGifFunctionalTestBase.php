<?php

declare(strict_types = 1);

namespace Drupal\Tests\animated_gif\Functional;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\animated_gif\Traits\AnimatedGifTestTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\UserInterface;

/**
 * Base class for Animated GIF functional tests.
 */
abstract class AnimatedGifFunctionalTestBase extends BrowserTestBase {
  use AnimatedGifTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'animated_gif',
    'node',
    'file',
    'image',
    'field_ui',
  ];

  /**
   * The display repository service.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected EntityDisplayRepositoryInterface $displayRepository;

  /**
   * A user with administration permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->displayRepository = $this->container->get('entity_display.repository');
    $this->fileSystem = $this->container->get('file_system');
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->moduleExtensionList = $this->container->get('extension.list.module');

    $this->adminUser = $this->drupalCreateUser([
      'access content',
      'access administration pages',
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer nodes',
      'bypass node access',
    ]);

    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);
  }

  /**
   * Helper method to create the image field.
   *
   * @param string $entityType
   *   The entity type.
   * @param string $bundle
   *   The bundle name.
   * @param string $fieldName
   *   The field name.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createFileField(string $entityType, string $bundle, string $fieldName): void {
    if (!FieldStorageConfig::loadByName($entityType, $fieldName)) {
      FieldStorageConfig::create([
        'field_name' => $fieldName,
        'entity_type' => $entityType,
        'type' => 'image',
        'settings' => [
          'alt_field_required' => 0,
        ],
        'cardinality' => 1,
      ])->save();
    }

    if (!FieldConfig::loadByName($entityType, $bundle, $fieldName)) {
      FieldConfig::create([
        'field_name' => $fieldName,
        'entity_type' => $entityType,
        'bundle' => $bundle,
        'label' => $fieldName,
      ])->save();
    }
  }

}
