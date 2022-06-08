<?php

namespace Drupal\printable\Tests;

use Drupal\Tests\node\Functional\NodeTestBase;
use Smalot\PdfParser\Parser;

$autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload)) {
  require_once $autoload;
}

/**
 * Tests the printable_pdf module functionality.
 *
 * @group printable
 */
class PrintablePdfTest extends NodeTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'printable',
    'printable_pdf',
    'pdf_api',
    'node_test_exception',
    'dblog',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Perform any initial set up tasks that run before every test method.
   */
  public function setUp() {
    parent::setUp();
    $web_user = $this->drupalCreateUser([
      'create page content',
      'edit own page content',
      'view printer friendly versions',
      'administer printable',
    ]);
    $this->drupalLogin($web_user);
  }

  /**
   * Tests that the 'node/{node}/printable/pdf' path returns the right content.
   */
  public function testCustomPageExists() {
    global $base_url;
    $node_type_storage = \Drupal::entityTypeManager()->getStorage('node_type');

    // Test /node/add page with only one content type.
    $node_type_storage->load('article')->delete();
    $this->drupalGet('node/add');
    $this->assertResponse(200);
    $this->assertUrl('node/add/page');
    // Create a node.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $bodytext = $this->randomMachineName(16) . 'This is functional test which I am writing for printable module.';
    $edit['body[0][value]'] = $bodytext;
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

    // Set the PDF generating tool.
    $this->drupalGet('admin/config/user-interface/printable/pdf');
    $this->drupalPostForm(NULL, [
      'print_pdf_pdf_tool' => 'mPDF',
      'print_pdf_content_disposition' => 1,
      'print_pdf_filename' => 'modules/custom/printable/src/Tests/testPDF',
    ], t('Submit'));
    $this->drupalGet('admin/config/user-interface/printable/pdf');
    $this->assertResponse(200);

    // Test whether PDF page is being generated.
    $this->drupalGet('node/' . $node->id() . '/printable/pdf');
    $parser = new Parser();
    $pdf = $parser->parseFile('modules/custom/printable/src/Tests/testPDF.pdf');

    $text = $pdf->getText();

    $this->drupalGet('node/add');

    $new_edit = [];
    $new_edit['title[0][value]'] = $this->randomMachineName(8);
    $bodytext = $text;
    $new_edit['body[0][value]'] = $bodytext;
    $this->drupalPostForm('node/add/page', $new_edit, t('Save'));
    $new_node = $this->drupalGetNodeByTitle($new_edit['title[0][value]']);
    $this->drupalGet('node/' . $new_node->id());
    $this->assertResponse(200);

    // Checks the presence of body in the page.
    $this->assertRaw($edit['body[0][value]'], 'Body discovered successfully in the printable page');

    // Check if footer is rendering correctly.
    $this->assertRaw($base_url . '/node/' . $node->id(), 'Source Url discovered in the printable page');
  }

}
