<?php

declare(strict_types = 1);

namespace Drupal\Tests\animated_gif\Functional;

use Drupal\file\FileInterface;
use Drupal\node\NodeInterface;

/**
 * Tests fields styles.
 *
 * @group animated_gif
 */
class AnimatedGifFieldTest extends AnimatedGifFunctionalTestBase {

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
   * The tested node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected NodeInterface $node;

  /**
   * The field name.
   *
   * @var string
   */
  protected string $fieldName = 'field_image';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $entityType = 'node';
    $bundle = 'article';

    // Attach file to the node.
    $this->createFileField($entityType, $bundle, $this->fieldName);

    // Create the node.
    $this->node = $this->drupalCreateNode([
      'type' => $bundle,
    ]);
    $this->node->save();

    $this->displayRepository->getFormDisplay($entityType, $bundle)
      ->setComponent($this->fieldName, [
        'type' => 'image_image',
      ])
      ->save();
  }

  /**
   * Method to test gif images.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testGif(): void {
    $this->gifAnimationTest(self::TEST_ANIMATED_FILE, self::TEST_ANIMATED_FILE_URI, TRUE);
    $this->gifAnimationTest(self::TEST_NOT_ANIMATED_FILE, self::TEST_NOT_ANIMATED_FILE_URI, FALSE);
  }

  /**
   * Helper method to test the image styles modifications.
   *
   * @param string $fileName
   *   The file name.
   * @param string $fileUri
   *   The file uri.
   * @param bool $isAnimated
   *   Set if it's animated.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function gifAnimationTest(string $fileName, string $fileUri, bool $isAnimated): void {
    $this->drupalLogin($this->adminUser);

    $nid = $this->node->id();
    $this->drupalGet("node/{$nid}/edit");

    $file = $this->getTestFile($fileName, $fileUri);
    $this->uploadImage($file);

    if ($isAnimated) {
      $this->assertSession()->pageTextContains('GIF images are not being processed by image styles, use with caution!');
    }
    else {
      $this->assertSession()->pageTextNotContains('GIF images are not being processed by image styles, use with caution!');
    }
  }

  /**
   * Helper method to upload $file on the node.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file.
   */
  protected function uploadImage(FileInterface $file): void {
    $edit = [
      'files[' . $this->fieldName . '_0]' => $this->fileSystem->realpath($file->getFileUri()),
    ];
    $this->submitForm($edit, 'Upload');
  }

}
