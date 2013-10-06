<?php

/**
 * @file
 * PHPUnit tests for \Drupal\sapi\Plugin\StatDataAnnotatedClassDiscovery.
 */

namespace Drupal\sapi\Tests;

use Drupal\Tests\UnitTestCase;
use Drupal\sapi\Plugin\StatDataAnnotatedClassDiscovery;

/**
 * Tests the Statistics Data annotated class discovery.
 *
 * @group SAPI
 */
class StatDataAnnotatedClassDiscoveryTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Statistics data annotated class discovery test',
      'description' => 'Unit tests for class discovery of stat data.',
      'group' => 'sapi'
    );
  }

  /**
   * Tests \Drupal\sapi\Plugin\StatDataAnnotatedClassDiscovery::processDefinition().
   */
  public function testDataClassDiscoveryProcessDefinition() {
    $plugin_id = 'test_data';
    $definition = array('schema' => array());

    $entity_manager = $this->getMockBuilder('Drupal\Core\Entity\EntityManager')
      ->disableOriginalConstructor()
      ->getMock();
    $storage_controller = $this->getMockBuilder('Drupal\sapi\StatStorageController')
      ->disableOriginalConstructor()
      ->getMock();

    // Ensure the stat property is ensured on the storage controller.
    $storage_controller->expects($this->once())
      ->method('ensureProperty')
      ->with($plugin_id);

    // Ensure the storage controller is pulled from the entity manager.
    $entity_manager->expects($this->once())
      ->method('getStorageController')
      ->with('stat')
      ->will($this->returnValue($storage_controller));

    // Instantiate the discovery class and manually process a fake definition.
    $namespaces = new \ArrayObject(array('Drupal\plugin_test' => DRUPAL_ROOT . '/core/modules/system/tests/modules/plugin_test/lib'));
    $discovery = new StatDataAnnotatedClassDiscovery('Stat/data', $namespaces, $entity_manager);
    $discovery->processDefinition($definition, $plugin_id);
  }
}
