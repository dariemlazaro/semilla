<?php

namespace Drupal\Tests\high_contrast\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\high_contrast\HighContrastTrait;

/**
 * Test to ensure that changes to the stylesheet are shown in the (cached) page.
 *
 * @group high_contrast
 */
class HighContrastStyleSheetTest extends WebDriverTestBase {

  use HighContrastTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'block',
    'high_contrast',
    // These should be added by the test profile as well, but let's be sure.
    'page_cache',
    'dynamic_page_cache',
  ];

  /**
   * Holds the values for later comparison.
   *
   * @var array
   */
  private $values = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Store the values for later comparison.
    $this->values = [
      'colors_background' => '#123123',
      'colors_text' => '#abcabc',
      'colors_hyperlinks' => '#defdef',
    ];

    // Update the configuration.
    $this->config('high_contrast.settings')->setData($this->values)->save();
  }

  /**
   * Test to see if a stylesheet is properly generated.
   */
  public function testStyleSheetGenerated() {

    // Ensure CSS file is generated.
    $file_path = HIGH_CONTRAST_CSS_LOCATION;
    $this->assertFileExists($file_path);

    // Assert all defined colors are present in the file.
    $css_file = file_get_contents($file_path);
    foreach ($this->values as $definition => $color) {
      $this->assertContains($color, $css_file, "Color $color has been applied for $definition.");
    }

    // Generate some new values.
    $new_values = [
      'colors_background' => '#456456',
      'colors_text' => '#789789',
      'colors_hyperlinks' => '#012345',
    ];
    // Update the configuration.
    $this->config('high_contrast.settings')->setData($new_values)->save();

    // Assert all previous colors are gone.
    $css_file = file_get_contents($file_path);
    foreach ($this->values as $definition => $color) {
      $this->assertNotContains($color, $css_file, "Color $color has not been applied for $definition.");
    }
    // Assert all new colors are present.
    foreach ($new_values as $definition => $color) {
      $this->assertContains($color, $css_file, "Color $color has been applied for $definition.");
    }
  }

}
