<?php

namespace Drupal\printable\Tests;

use Drupal\Tests\node\Functional\NodeTestBase;
use Drupal\block\Entity\Block;

/**
 * Tests the blocks present in printable module.
 *
 * @group printable
 */
class PrintableBlockTest extends NodeTestBase {

  /**
   * An administrative user for testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['printable', 'block', 'views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Perform any initial set up tasks that run before every test method.
   */
  public function setUp() {
    parent::setUp();

    // Create users and test node.
    $this->adminUser = $this->drupalCreateUser([
      'administer content types',
      'administer nodes',
      'administer blocks',
      'access content overview',
    ]);
    $this->webUser = $this->drupalCreateUser([
      'access content',
      'create article content',
    ]);
  }

  /**
   * Tests the functionality of the Printable block.
   */
  public function testPrintableBlock() {
    $this->drupalLogin($this->adminUser);
    $edit = [
      'id' => strtolower($this->randomMachineName()),
      'settings[label]' => $this->randomMachineName(8),
      'region' => 'sidebar_first',
      'visibility[node_type][bundles][article]' => 'article',
    ];
    $theme = \Drupal::service('theme_handler')->getDefault();
    $this->drupalPostForm("admin/structure/block/add/printable_links_block%3Anode/$theme", $edit, t('Save block'));

    $block = Block::load($edit['id']);
    $visibility = $block->getVisibility();
    $this->assertTrue(isset($visibility['node_type']['bundles']['article']), 'Visibility settings were saved to configuration');

    // Test deleting the block from the edit form.
    $this->drupalGet('admin/structure/block/manage/' . $edit['id']);
    $this->clickLink(t('Delete'));
    $this->assertRaw(t('Are you sure you want to delete the block %name?', ['%name' => $edit['settings[label]']]));
    $this->drupalPostForm(NULL, [], t('Delete'));
    $this->assertRaw(t('The block %name has been deleted.', ['%name' => $edit['settings[label]']]));
  }

}
