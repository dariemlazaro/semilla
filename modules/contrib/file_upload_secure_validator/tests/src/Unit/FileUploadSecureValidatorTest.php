<?php

namespace Drupal\Tests\file_upload_secure_validator\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\file_upload_secure_validator\Service\FileUploadSecureValidator;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * A class for unit testing the file_upload_secure_validator service.
 *
 * @group file_upload_secure_validator
 */
class FileUploadSecureValidatorTest extends UnitTestCase {

  /**
   * Tests the file upload validate function of the provided service.
   *
   * @dataProvider fileUploadScenariosProvider
   */
  public function testFileUploadSecureValidatorValidate($case, $uri, $mimetype, $expectedError) {
    // This is the main class of the service.
    $file_upload_secure_validator_service = new FileUploadSecureValidator(
      $this->getLoggerFactoryMock(),
      $this->getTranslationManagerMock(),
      $this->getConfigFactoryMock()
    );

    $errors = $file_upload_secure_validator_service->validate($this->mockFile($uri, $mimetype));
    $error = array_pop($errors);

    $this->assertEquals($error, $expectedError);
  }

  /**
   * Scenario related data are created in this function.
   */
  public function fileUploadScenariosProvider() {
    return [
      [
        'case' => 'True extension',
        'uri' => dirname(__FILE__) . '/resources/original_pdf.pdf',
        'mimetype' => 'application/pdf',
        'expectedError' => NULL,
      ],
      [
        'case' => 'Falsified extension',
        'uri' => dirname(__FILE__) . '/resources/original_pdf.txt',
        'mimetype' => 'text/plain',
        // Setting this up as a new TranslatableMarkup with our mock translation
        // manager; otherwise assertEquals complains about non-identical objects
        // based on the attached TranslationManager service.
        'expectedError' => new TranslatableMarkup('There was a problem with this file. The uploaded file is of type @extension but the real content seems to be @real_extension', [
          '@extension' => 'text/plain',
          '@real_extension' => 'application/pdf',
        ], [], $this->getTranslationManagerMock()),
      ],
      [
        'case' => 'CSV extension',
        'uri' => dirname(__FILE__) . '/resources/original_csv.csv',
        'mimetype' => 'text/csv',
        'expectedError' => NULL,
      ],
      [
        'case' => 'XML extension',
        'uri' => dirname(__FILE__) . '/resources/original_xml.xml',
        'mimetype' => 'text/xml',
        'expectedError' => NULL,
      ],
      [
        'case' => 'SVG extension',
        'uri' => dirname(__FILE__) . '/resources/original_svg_no_headers.svg',
        'mimetype' => 'image/svg+xml',
        'expectedError' => NULL,
      ],
      [
        'case' => 'PO extension',
        'uri' => dirname(__FILE__) . '/resources/original_po.po',
        'mimetype' => 'application/octet-stream',
        'expectedError' => NULL,
      ],
      [
        'case' => 'Certificate extensions',
        'uri' => dirname(__FILE__) . '/resources/original_crt.crt',
        'mimetype' => 'application/x-x509-ca-cert',
        'expectedError' => NULL,
      ],
      [
        'case' => 'OpenOffice docx extension',
        'uri' => dirname(__FILE__) . '/resources/original_docx.docx',
        'mimetype' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'expectedError' => NULL,
      ],
      [
        'case' => 'OpenOffice xlsx extension',
        'uri' => dirname(__FILE__) . '/resources/original_xlsx.xlsx',
        'mimetype' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'expectedError' => NULL,
      ],
    ];
  }

  /**
   * Mock file entities.
   *
   * We are only interested in the uri and mimetype getters.
   */
  private function mockFile($uri, $mimetype) {
    $fileMock = $this->getMockBuilder('Drupal\file\Entity\File')
      ->disableOriginalConstructor()
      ->getMock();
    $fileMock->expects($this->any())
      ->method('getFileUri')
      ->willReturn($uri);
    $fileMock->expects($this->any())
      ->method('getMimeType')
      ->willReturn($mimetype);

    return $fileMock;
  }

  /**
   * Utility function for getting a TranslationManager service.
   */
  private function getTranslationManagerMock() {

    $translationManager = $this->getMockBuilder('Drupal\Core\StringTranslation\TranslationManager')
      ->disableOriginalConstructor()
      ->getMock();
    $translationManager->expects($this->any())
      ->method('translate')
      ->will($this->returnArgument(0));

    return $translationManager;
  }

  /**
   * Utility function for getting a LoggerChannelFactory service.
   */
  private function getLoggerFactoryMock() {

    $loggerChannel = $this->getMockBuilder('Drupal\Core\Logger\LoggerChannel')
      ->disableOriginalConstructor()
      ->getMock();
    $loggerChannel->expects($this->any())
      ->method('error')
      ->will($this->returnValue(''));

    $loggerChannelFactory = $this->getMockBuilder('Drupal\Core\Logger\LoggerChannelFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $loggerChannelFactory->expects($this->any())
      ->method('get')
      ->will($this->returnValue($loggerChannel));
    return $loggerChannelFactory;
  }

  /**
   * Utility function for getting a ConfigFactory service.
   */
  private function getConfigFactoryMock() {
    $mimeTypesEquivalenceGroups = [
      [
        'text/csv',
        'text/plain',
        'application/csv',
        'text/comma-separated-values',
        'application/excel',
        'application/vnd.ms-excel',
        'application/vnd.msexcel',
        'text/anytext',
        'application/octet-stream',
        'application/txt',
      ],
      [
        'text/xml',
        'text/plain',
        'application/xml',
      ],
      [
        'image/svg+xml',
        'image/svg',
      ],
      [
        'text/x-po',
        'application/octet-stream',
      ],
      [
        'text/plain',
        'application/pkix-cert',
        'application/pkix-crl',
        'application/x-x509-ca-cert',
        'application/x-x509-user-cert',
        'application/x-pem-file',
        'application/pgp-keys',
        'application/x-pkcs7-certificates',
        'application/x-pkcs7-certreqresp',
        'application/x-pkcs7-crl',
        'application/pkcs7-mime',
        'application/pkcs8',
        'application/pkcs10',
        'application/x-pkcs12',
      ],
      [
        'application/octet-stream',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      ],
    ];
    $configuration = $this->getMockBuilder('Drupal\Core\Config\ConfigFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $configuration->expects($this->any())
      ->method('get')
      ->with($this->equalTo('mime_types_equivalence_groups'))
      ->will($this->returnValue($mimeTypesEquivalenceGroups));

    $configFactory = $this->getMockBuilder('Drupal\Core\Config\ConfigFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $configFactory->expects($this->any())
      ->method('get')
      ->with($this->equalTo('file_upload_secure_validator.settings'))
      ->will($this->returnValue($configuration));
    return $configFactory;
  }

}
