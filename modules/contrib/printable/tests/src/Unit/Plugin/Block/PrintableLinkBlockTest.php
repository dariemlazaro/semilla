<?php

namespace Drupal\Tests\printable\Unit\Plugin\Block;

use Drupal\Tests\UnitTestCase;
use Drupal\printable\Plugin\Block\PrintableLinksBlock;

/**
 * Tests the printable links block plugin.
 *
 * @group Printable
 */
class PrintableLinkBlockTest extends UnitTestCase {

  protected $configuration = [];

  protected $pluginId;

  protected $pluginDefinition = [];

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    parent::__construct();
    $this->pluginId = 'printable_links_block:node';
    $this->pluginDefinition['module'] = 'printable';
    $this->pluginDefinition['provider'] = '';
  }

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return [
      'name' => 'Printable Block',
      'descriptions' => 'Tests the printable block plugin class.',
      'group' => 'Printable',
    ];
  }

  /**
   * Tests the block build method.
   *
   * @covers PrintableLinksBlock::build
   */
  public function testBuild() {
    $routematch = $this->getMockBuilder('Drupal\Core\Routing\CurrentRouteMatch')
      ->disableOriginalConstructor()
      ->setMethods(['getMasterRouteMatch', 'getParameter'])
      ->getMock();
    $routematch->expects($this->exactly(2))
      ->method('getMasterRouteMatch')
      ->will($this->returnSelf());
    $routematch->expects($this->exactly(2))
      ->method('getParameter')
      ->will($this->returnValue($this->createMock('Drupal\Core\Entity\EntityInterface')));
    $links = [
      'title' => 'Print',
      'url' => '/foo/1/printable/print',
      'attributes' => [
        'target' => '_blank',
      ],
    ];
    $links_builder = $this->getMockBuilder('Drupal\printable\PrintableLinkBuilderInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $links_builder->expects($this->once())
      ->method('buildLinks')
      ->will($this->returnValue($links));

    $block = new PrintableLinksBlock($this->configuration, $this->pluginId, $this->pluginDefinition, $routematch, $links_builder);

    $expected_build = [
      '#theme' => 'links__entity__printable',
      '#links' => $links,
    ];
    $this->assertEquals($expected_build, $block->build());
  }

}
