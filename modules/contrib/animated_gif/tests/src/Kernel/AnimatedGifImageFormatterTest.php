<?php

declare(strict_types = 1);

namespace Drupal\Tests\animated_gif\Kernel;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\animated_gif\Traits\AnimatedGifTestTrait;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;

/**
 * Tests URL image formatter.
 *
 * @group animated_gif
 */
class AnimatedGifImageFormatterTest extends FieldKernelTestBase {
  use AnimatedGifTestTrait;

  /**
   * Name of the animated file used for testing.
   */
  public const TEST_ANIMATED_FILE = 'animated.gif';

  /**
   * URI of the file used for testing.
   */
  public const TEST_ANIMATED_FILE_URI = 'temporary://' . self::TEST_ANIMATED_FILE;

  /**
   * Name of the not animated file used for testing.
   */
  public const TEST_NOT_ANIMATED_FILE = 'not-animated.gif';

  /**
   * URI of the not animated file used for testing.
   */
  public const TEST_NOT_ANIMATED_FILE_URI = 'temporary://' . self::TEST_NOT_ANIMATED_FILE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
    'image',
    'animated_gif',
  ];

  /**
   * The entity type.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The field name.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The entity view display.
   *
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   */
  protected $display;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig([
      'field',
      'image',
    ]);
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);

    $this->fileSystem = $this->container->get('file_system');
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->moduleExtensionList = $this->container->get('extension.list.module');
    $this->fileUrlGenerator = $this->container->get('file_url_generator');
    $this->entityType = 'entity_test';
    $this->bundle = $this->entityType;
    $this->fieldName = \mb_strtolower($this->randomMachineName());

    FieldStorageConfig::create([
      'entity_type' => $this->entityType,
      'field_name' => $this->fieldName,
      'type' => 'image',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();
    FieldConfig::create([
      'entity_type' => $this->entityType,
      'field_name' => $this->fieldName,
      'bundle' => $this->bundle,
      'settings' => [
        'file_extensions' => 'gif',
      ],
    ])->save();

    $this->display = $this->container->get('entity_display.repository')
      ->getViewDisplay($this->entityType, $this->bundle)
      ->setComponent($this->fieldName, [
        'type' => 'animated_gif_image_url',
        'label' => 'hidden',
        'settings' => [
          'image_style' => 'medium',
        ],
      ]);
    $this->display->save();
  }

  /**
   * Tests Image Formatter URL options handling.
   */
  public function testAnimatedGifImageUrlFormatter(): void {
    $animatedGifFile = $this->getTestFile(self::TEST_ANIMATED_FILE, self::TEST_ANIMATED_FILE_URI);
    $notAnimatedGifFile = $this->getTestFile(self::TEST_NOT_ANIMATED_FILE, self::TEST_NOT_ANIMATED_FILE_URI);

    // Create a test entity with the image field set.
    $entity = EntityTest::create([
      'name' => $this->randomMachineName(),
      $this->fieldName => [
        $animatedGifFile,
        $notAnimatedGifFile,
      ],
    ]);

    $entity->save();

    // Generate the render array to verify markup.
    $build = $this->display->build($entity);

    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');

    $output = $renderer->renderRoot($build[$this->fieldName][0]);
    $this->assertStringContainsString($this->fileUrlGenerator->transformRelative($this->fileUrlGenerator->generateString($animatedGifFile->getFileUri())), (string) $output);

    $output = $renderer->renderRoot($build[$this->fieldName][1]);
    $this->assertStringNotContainsString($this->fileUrlGenerator->transformRelative($this->fileUrlGenerator->generateString($notAnimatedGifFile->getFileUri())), (string) $output);

    /** @var \Drupal\image\ImageStyleInterface $image_style */
    $image_style = $this->entityTypeManager
      ->getStorage('image_style')
      ->load('medium');

    // For non animated gifs, Url should be with image style Url.
    $this->assertStringContainsString($this->fileUrlGenerator->transformRelative($image_style->buildUrl($notAnimatedGifFile->getFileUri())), \htmlspecialchars_decode((string) $output));
  }

}
