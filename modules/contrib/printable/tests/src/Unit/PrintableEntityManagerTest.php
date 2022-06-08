<?php

namespace Drupal\Tests\printable\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\printable\PrintableEntityManager;

/**
 * Tests the printable entity manager plugin.
 *
 * @group Printable
 */
class PrintableEntityManagerTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return [
      'name' => 'Printable Entity Manager',
      'descriptions' => 'Tests the printable entity manager class.',
      'group' => 'Printable',
    ];
  }

  /**
   * Tests getting the printable entities.
   *
   * @covers PrintableEntityManager::GetPrintableEntities
   */
  public function testGetPrintableEntities() {
    // Construct a printable entity manager and it's dependencies.
    $entity_definition = $this->getMockBuilder('Drupal\Core\Entity\EntityType')
      ->disableOriginalConstructor()
      ->getMock();
    $entity_definition->expects($this->any())
      ->method('hasHandlerClass')
      ->will($this->returnValue(TRUE));
    $entity_manager = $this->createMock('Drupal\Core\Entity\EntityManagerInterface');
    $entity_manager->expects($this->once())
      ->method('getDefinitions')
      ->will($this->returnValue([
        'node' => $entity_definition,
        'comment' => $entity_definition,
      ])
      );
    $config = $this->getConfigFactoryStub([
      'printable.settings' => [
        'printable_entities' => ['node', 'comment', 'bar'],
      ],
    ]);
    $printable_entity_manager = new PrintableEntityManager($entity_manager, $config);

    // Verify getting the printable entities.
    $expected_entity_definitions = [
      'node' => $entity_definition,
      'comment' => $entity_definition,
    ];
    $this->assertEquals($expected_entity_definitions, $printable_entity_manager->getPrintableEntities());
  }

}
