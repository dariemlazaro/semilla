<?php

namespace Drupal\printable\Tests;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the printable module functionality.
 *
 * @group printable
 */
class PrintableFormTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['printable'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A simple user with 'administer printable' permission.
   *
   * @var \Drupal\user\Entity\User
   */
  private $user;

  /**
   * Perform any initial set up tasks that run before every test method.
   */
  public function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser(['administer printable']);
    $this->drupalLogin($this->user);
  }

  /**
   * Tests the Print form.
   */
  public function testPrintFormWorks() {
    $this->drupalLogin($this->user);
    $this->drupalGet('admin/config/user-interface/printable/print');
    $this->assertResponse(200);

    $config = $this->config('printable.settings');
    $this->assertFieldByName('print_html_sendtoprinter', $config->get('printable.send_to_printer'), 'The field was found with the correct value.');

    $this->drupalPostForm(NULL, [
      'print_html_sendtoprinter' => 1,
    ], t('Submit'));
    $this->drupalGet('admin/config/user-interface/printable/print');
    $this->assertResponse(200);
    $this->assertFieldByName('print_html_sendtoprinter', 1, 'The field was found with the correct value.');
  }

}
