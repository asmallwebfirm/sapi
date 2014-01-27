<?php

/**
 * @file
 * PHPUnit tests for \Drupal\sapi\StatMethodStorageController.
 */

namespace Drupal\sapi\Tests;

use Drupal\Core\Entity\EntityType;
use Drupal\Tests\UnitTestCase;
use Drupal\sapi\StatMethodStorageController;


// SAVED_UPDATED is not defined, but is used in the storage controller class.
if (!defined('SAVED_UPDATED')) {
  define('SAVED_UPDATED', 2);
}

/**
 * Tests the Statistics Method storage controller.
 *
 * @group SAPI
 */
class StatMethodStorageControllerTest extends UnitTestCase {

  /**
   * Default entity info for the Stat Method entity.
   */
  protected $entity_info = array(
    'id' => 'stat_method',
    'class' => 'Drupal\sapi\Entity\StatMethod',
    'config_prefix' => 'sapi.method',
  );

  /**
   * Default values for the Stat Method entity.
   */
  protected $entity_defaults = array(
    'id' => 'test_method',
    'status' => 1,
  );

  public static function getInfo() {
    return array(
      'name' => 'Statistics method storage controller test',
      'description' => 'Unit test of the base plugin for Statistics methods.',
      'group' => 'sapi'
    );
  }

  /**
   * Tests \Drupal\sapi\StatMethodStorageController::create().
   */
  public function testStorageControllerCreate() {
    $entity_info = new EntityType($this->entity_info);
    $plugin_manager = $this->getMockBuilder('Drupal\sapi\Plugin\StatPluginManager')
      ->disableOriginalConstructor()
      ->getMock();
    $config_factory = $this->getMockBuilder('Drupal\Core\Config\ConfigFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $module_handler = $this->getMock('Drupal\Core\Extension\ModuleHandler');
    $query_factory = $this->getMockBuilder('Drupal\Core\Entity\Query\QueryFactory')
      ->disableOriginalConstructor()
      ->getMock();

    // Ensure that the proper hooks are invoked.
    $module_handler->expects($this->at(0))
      ->method('invokeAll')
      ->with($entity_info->id() . '_create');
    $module_handler->expects($this->at(1))
      ->method('invokeAll')
      ->with('entity_create');

    // Use the controller to create a Stat Method entity.
    $controller = new StatMethodStorageController($entity_info, $plugin_manager, $config_factory, $module_handler, $query_factory);
    $entity = $controller->create($this->entity_defaults);

    // Ensure the resulting entity makes sense.
    $this->assertEquals($entity_info->getClass(), get_class($entity));
    $this->assertEquals($this->entity_defaults['status'], $entity->get('status'));
  }

  /**
   * Tests \Drupal\sapi\StatMethodStorageController::save().
   */
  public function testStorageControllerSave() {
    $entity_info = new EntityType($this->entity_info);
    $plugin_manager = $this->getMockBuilder('Drupal\sapi\Plugin\StatPluginManager')
      ->disableOriginalConstructor()
      ->getMock();
    $config_factory = $this->getMockBuilder('Drupal\Core\Config\ConfigFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $config = $this->getMockBuilder('Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();
    $module_handler = $this->getMock('Drupal\Core\Extension\ModuleHandler');
    $query_factory = $this->getMockBuilder('Drupal\Core\Entity\Query\QueryFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $stat_method = $this->getMockBuilder('Drupal\sapi\Entity\StatMethod')
      ->setConstructorArgs(array($this->entity_defaults, $entity_info->id()))
      ->getMock();

    // Instantiate the controller early; we need it for some assertions.
    $controller = new StatMethodStorageController($entity_info, $plugin_manager, $config_factory, $module_handler, $query_factory);

    // The stat method should return a non-null/empty ID.
    $stat_method->expects($this->any())
      ->method('id')
      ->will($this->returnValue($this->entity_defaults['id']));

    // Plugin manager needs to return the expected plugins.
    $plugin_manager->expects($this->any())
      ->method('getDefinitions')
      ->will($this->returnValue(array()));

    // Ensure that the config factory is called and returns a config object.
    $config_factory->expects($this->once())
      ->method('get')
      ->with($this->entity_info['config_prefix'] . '.' . $this->entity_defaults['id'])
      ->will($this->returnValue($config));

    // Ensure that export properties are retrieved and, in turn, set.
    $stat_method->expects($this->once())
      ->method('getExportProperties')
      ->will($this->returnValue($this->entity_defaults));
     $config->expects($this->exactly(count($this->entity_defaults)))
       ->method('set');

    // Ensure that all entity create methods/hooks are called.
    $stat_method->expects($this->once())
      ->method('preSave')
      ->with($controller);
    $stat_method->expects($this->once())
      ->method('postSave')
      ->with($controller, TRUE);
    $module_handler->expects($this->at(0))
      ->method('invokeAll')
      ->with($entity_info->id() . '_presave');
    $module_handler->expects($this->at(1))
      ->method('invokeAll')
      ->with('entity_presave');
    $module_handler->expects($this->at(2))
      ->method('invokeAll')
      ->with($entity_info->id() . '_update');
    $module_handler->expects($this->at(3))
      ->method('invokeAll')
      ->with('entity_update');

    // Ensure the plugin manager clears its cache on save.
    $plugin_manager->expects($this->once())
      ->method('clearCachedDefinitions');

    // Call the controller save method.
    $controller->save($stat_method);
  }

  /**
   * Tests \Drupal\sapi\StatMethodStorageController::loadMultiple().
   */
  public function testStorageControllerLoadMultiple() {
    $entity_info = new EntityType($this->entity_info);
    $plugin_manager = $this->getMockBuilder('Drupal\sapi\Plugin\StatPluginManager')
      ->disableOriginalConstructor()
      ->getMock();
    $config_factory = $this->getMockBuilder('Drupal\Core\Config\ConfigFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $module_handler = $this->getMock('Drupal\Core\Extension\ModuleHandler');
    $query_factory = $this->getMockBuilder('Drupal\Core\Entity\Query\QueryFactory')
      ->disableOriginalConstructor()
      ->getMock();

    // Ensure plugin definitions are loaded from the plugin manager.
    $plugin_manager->expects($this->once())
      ->method('getDefinitions')
      ->will($this->returnValue(array($this->entity_defaults['id'] => $this->entity_defaults)));

    // Ensure the module handler invokes the proper hooks.
    $module_handler->expects($this->at(0))
      ->method('invokeAll')
      ->with($entity_info->id() . '_create');
    $module_handler->expects($this->at(1))
      ->method('invokeAll')
      ->with('entity_create');
    $module_handler->expects($this->at(2))
      ->method('getImplementations')
      ->with('entity_load')
      ->will($this->returnValue(array()));
    $module_handler->expects($this->at(3))
      ->method('getImplementations')
      ->with($entity_info->id() . '_load')
      ->will($this->returnValue(array()));

    // Use the controller to load multiple stat method entities.
    $controller = new StatMethodStorageController($entity_info, $plugin_manager, $config_factory, $module_handler, $query_factory);
    $entities = $controller->loadMultiple(array($this->entity_defaults['id']));

    // Ensure the correct entity was loaded.
    $this->assertEquals(TRUE, isset($entities[$this->entity_defaults['id']]));
    $this->assertEquals('Drupal\sapi\Entity\StatMethod', get_class($entities[$this->entity_defaults['id']]));
    $this->assertEquals($this->entity_defaults['status'], $entities[$this->entity_defaults['id']]->get('status'));
  }

  /**
   * Tests \Drupal\sapi\StatMethodStorageController ancillary load methods.
   */
  public function testStorageControllerAncillaryLoads() {
    $entity_info = new EntityType($this->entity_info);
    $plugin_manager = $this->getMockBuilder('Drupal\sapi\Plugin\StatPluginManager')
      ->disableOriginalConstructor()
      ->getMock();
    $config_factory = $this->getMockBuilder('Drupal\Core\Config\ConfigFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $module_handler = $this->getMock('Drupal\Core\Extension\ModuleHandler');
    $query_factory = $this->getMockBuilder('Drupal\Core\Entity\Query\QueryFactory')
      ->disableOriginalConstructor()
      ->getMock();

    // Plugin manager needs to return a plugin.
    $plugin_manager->expects($this->any())
      ->method('getDefinitions')
      ->will($this->returnValue(array($this->entity_defaults['id'] => $this->entity_defaults)));

    // Module handler needs to return an array.
    $module_handler->expects($this->any())
      ->method('getImplementations')
      ->will($this->returnValue(array()));

    // Use the controller to load multiple stat method entities.
    $controller = new StatMethodStorageController($entity_info, $plugin_manager, $config_factory, $module_handler, $query_factory);
    $entities = $controller->loadMultiple(array($this->entity_defaults['id']));

    // Ensure that a non-existent entity is not loaded.
    $this->assertEquals(NULL, $controller->load('not_a_real_entity'));

    // Ensure that a defined entity is loaded.
    $this->assertEquals($entities[$this->entity_defaults['id']], $controller->load($this->entity_defaults['id']));

    // Ensure that revisions aren't a thing.
    $this->assertEquals(FALSE, $controller->loadRevision('anything'));

    // Ensure that loadByProperties loads all entities by default.
    $this->assertEquals($entities, $controller->loadByProperties());

    // Ensure that loadByProperties properly filters by given properties.
    $this->assertEquals($entities, $controller->loadByProperties($this->entity_defaults));
    $this->assertEquals(array(), $controller->loadByProperties(array('foo' => 'bar')));
  }

  /**
   * Tests \Drupal\sapi\StatMethodStorageController query handling.
   */
  public function testStorageControllerQueryHandling() {
    $entity_info = new EntityType($this->entity_info);
    $plugin_manager = $this->getMockBuilder('Drupal\sapi\Plugin\StatPluginManager')
      ->disableOriginalConstructor()
      ->getMock();
    $config_factory = $this->getMockBuilder('Drupal\Core\Config\ConfigFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $module_handler = $this->getMock('Drupal\Core\Extension\ModuleHandler');
    $query_factory = $this->getMockBuilder('Drupal\Core\Entity\Query\QueryFactory')
      ->disableOriginalConstructor()
      ->getMock();

    // Ensure the query factory is used properly.
    $query_factory->expects($this->any())
      ->method('get')
      ->with($entity_info->id(), 'AND')
      ->will($this->returnValue(TRUE));

    // Use the controller to test query handling.
    $controller = new StatMethodStorageController($entity_info, $plugin_manager, $config_factory, $module_handler, $query_factory);

    // Ensure the correct config prefix is returned.
    $this->assertEquals($entity_info->getConfigPrefix() . '.', $controller->getConfigPrefix());

    // Ensure the correct query service name is returned.
    $this->assertEquals('entity.query.config', $controller->getQueryServicename());

    // Ensure the correct query is returned.
    $this->assertEquals(TRUE, $controller->getQuery('AND'));
  }

}
