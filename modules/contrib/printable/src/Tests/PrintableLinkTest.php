<?php

namespace Drupal\printable\Tests;

use Drupal\Tests\node\Functional\NodeTestBase;

/**
 * Tests the printable module functionality.
 *
 * @group printable
 */
class PrintableLinkTest extends NodeTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['printable', 'node_test_exception', 'dblog'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Perform any initial set up tasks that run before every test method.
   */
  public function setUp() {
    parent::setUp();
    $user = $this->drupalCreateUser([
      'create page content',
      'edit own page content',
      'view printer friendly versions',
      'administer printable',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Tests that the links are rendered correctly in the page.
   */
  public function testPrintLinkExists() {
    $this->drupalGet('admin/config/user-interface/printable/links');
    $this->assertResponse(200);
    // Enable the print link in content area.
    $this->drupalPostForm(NULL, [
      'print_print_link_pos' => 'node',
    ], t('Submit'));
    $this->drupalGet('admin/config/user-interface/printable/pdf');
    $this->assertResponse(200);

    $node_type_storage = \Drupal::entityTypeManager()->getStorage('node_type');

    // Test /node/add page with only one content type.
    $node_type_storage->load('article')->delete();
    $this->drupalGet('node/add');

    // Create a node.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit['body[0][value]'] = $this->randomMachineName(16);
    $this->drupalPostForm('node/add/page', $edit, t('Save'));

    // Check that the Basic page has been created.
    $this->assertRaw(t('@post %title has been created.', [
      '@post' => 'Basic page',
      '%title' => $edit['title[0][value]'],
    ]), 'Basic page created.');

    // Check that the node exists in the database.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertTrue($node, 'Node found in database.');

    // Verify that pages do not show submitted information by default.
    $this->drupalGet('node/' . $node->id());
    $this->assertResponse(200);

    $this->assertRaw('Print', 'Print link discovered successfully in the printable page');
  }

}
