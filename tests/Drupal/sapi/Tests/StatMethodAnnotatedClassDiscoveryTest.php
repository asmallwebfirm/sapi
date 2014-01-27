<?php

/**
 * @file
 * PHPUnit tests for \Drupal\sapi\Plugin\StatMethodAnnotatedClassDiscovery.
 */

namespace Drupal\sapi\Tests;

use Drupal\Core\Entity\EntityType;
use Drupal\Tests\UnitTestCase;
use Drupal\sapi\Plugin\StatMethodAnnotatedClassDiscovery;

/**
 * Tests the Statistics Method annotated class discovery.
 *
 * @group SAPI
 */
class StatMethodAnnotatedClassDiscoveryTest extends UnitTestCase {

  /**
   * Stub definition of a StatMethod entity.
   */
  protected $entity_definition = array(
    'class' => 'Drupal\sapi\Entity\StatMethod',
    'config_prefix' => 'stat.method',
    'entity_keys' => array(
      'id' => 'id',
    ),
  );

  public static function getInfo() {
    return array(
      'name' => 'Statistics method annotated class discovery test',
      'description' => 'Unit tests for class discovery of stat methods.',
      'group' => 'sapi'
    );
  }

  /**
   * Tests \Drupal\sapi\StatMethodStorageController::create().
   */
  public function testStorageControllerCreate() {
    $entity_info = new EntityType($this->entity_definition);
    $entity_manager = $this->getMockBuilder('Drupal\Core\Entity\EntityManager')
      ->disableOriginalConstructor()
      ->getMock();
    $config_factory = $this->getMockBuilder('Drupal\Core\Config\ConfigFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $config = $this->getMockBuilder('Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();
    $namespaces = new \ArrayObject(array('Drupal\plugin_test' => DRUPAL_ROOT . '/core/modules/system/tests/modules/plugin_test/lib'));

    // Ensure the stat_method definition is pulled from the entity manager.
    $entity_manager->expects($this->once())
      ->method('getDefinition')
      ->with('stat_method')
      ->will($this->returnValue($entity_info));

    // Ensure the config mock we return is handled as expected.
    $config->id = 'stat_method_id';
    $config->expects($this->at(0))
      ->method('get')
      ->with('id')
      ->will($this->returnValue($config->id));
    $config->expects($this->at(1))
      ->method('get')
      ->will($this->returnValue(array()));

    // Ensure configurations are loaded from the config factory.
    $config_factory->expects($this->once())
      ->method('loadMultiple')
      ->with(array())
      ->will($this->returnValue(array($config)));

    // Instantiate the discovery class and get all definitions.
    $discovery = new StatMethodAnnotatedClassDiscovery('Stat/method', $namespaces, $entity_manager, $config_factory);
    $definitions = $discovery->getDefinitions();

    // @todo It would be ideal to test the actual merger of annotated class
    // definition and config entity definition, but doing so would require a bit
    // more work (regular annotation discovery must return a stat method).
    $this->assertEquals(array(), $definitions);
  }

}
