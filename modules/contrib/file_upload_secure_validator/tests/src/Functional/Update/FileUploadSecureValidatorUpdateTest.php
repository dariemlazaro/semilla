<?php

namespace Drupal\Tests\file_upload_secure_validator\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests adding file_upload_secure_valitator module's configuration.
 *
 * @group Update
 */
class FileUploadSecureValidatorUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../fixtures/update/drupal-8.file_upload_secure_validator.php.gz',
    ];
  }

  /**
   * Tests upgrading configuration.
   */
  public function testUpdateConfiguration() {
    // Run updates.
    $this->runUpdates();

    $config = $this->config('file_upload_secure_validator.settings');
    $this->assertNotNull($config->get('mime_types_equivalence_groups'));
  }

}
