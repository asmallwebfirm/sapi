<?php

/**
 * @file
 * PHPUnit tests for \Drupal\sapi\Plugin\StatPluginManager.
 */

namespace Drupal\sapi\Tests;

use Drupal\sapi\Plugin\StatPluginManager;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the default Stat storage controller.
 *
 * @group SAPI
 */
class StatPluginManagerTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Statistics plugin manager test',
      'description' => 'Unit tests for the stat plugin manager.',
      'group' => 'sapi'
    );
  }

  /**
   * Tests \Drupal\sapi\StatPluginManager::__construct().
   */
  public function testPluginManagerConstructorException() {
    // Stub database connection.
    $namespaces = $this->getMockBuilder('ArrayObject')
      ->disableOriginalConstructor()
      ->getMock();
    $entity_manager = $this->getMockBuilder('Drupal\Core\Entity\EntityManager')
      ->disableOriginalConstructor()
      ->getMock();
    $config = $this->getMockBuilder('Drupal\Core\Config\ConfigFactory')
      ->disableOriginalConstructor()
      ->getMock();

    // Set our expected exception.
    $this->setExpectedException('Drupal\sapi\Plugin\StatPluginException');

    // Instantiate the plugin manager with an invalid plugin type.
    $manager = new StatPluginManager('neither_data_nor_method', $namespaces, $entity_manager, $config);
  }

}
