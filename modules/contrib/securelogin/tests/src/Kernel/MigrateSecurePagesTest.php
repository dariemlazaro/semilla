<?php

namespace Drupal\Tests\securelogin\Kernel;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Migrates securepages configuration.
 *
 * @group Secure login
 */
class MigrateSecurePagesTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'securelogin',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getFixtureFilePath() {
    return implode(DIRECTORY_SEPARATOR, [
      \Drupal::service('extension.list.module')->getPath('securelogin'),
      'tests',
      'fixtures',
      'drupal7.php',
    ]);
  }

  /**
   * Tests the migration.
   */
  public function testMigration() {
    $config_before = $this->config('securelogin.settings');
    $this->assertEmpty($config_before->get('base_url'));
    $this->executeMigration('securepages_settings');
    $config_after = $this->config('securelogin.settings');
    $this->assertEquals('https://git.drupalcode.org/', $config_after->get('base_url'));
  }

}
