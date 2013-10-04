<?php

/**
 * @file
 * PHPUnit tests for \Drupal\sapi\StatMethodPluginBase.
 */

namespace Drupal\sapi\Tests;

use Drupal\Tests\UnitTestCase;


/**
 * Tests the Statistics Method Plugin base.
 *
 * @group SAPI
 */
class StatMethodPluginBaseTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Statistics method plugin base test',
      'description' => 'Unit test of the base plugin for Statistics methods.',
      'group' => 'sapi'
    );
  }

  protected function setUp() {
    $this->pluginId = 'test_method';
    $this->pluginConfig = array(
      'status' => 1,
      'label' => 'Test method',
      'settings' => array(
        'sapi' => array(
          'dnt' => 0,
          'retention_length' => 0,
        ),
      ),
    );
    $this->pluginDefinition = array();
    $this->namespaces = new \ArrayObject(array('Drupal\plugin_test' => DRUPAL_ROOT . '/core/modules/system/tests/modules/plugin_test/lib'));
  }

  /**
   * Tests \Drupal\sapi\StatMethodPluginBase config getter methods.
   */
  public function testConfigGetters() {
    $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
    $entity_manager = $this->getMockBuilder('Drupal\Core\Entity\EntityManager')
      ->disableOriginalConstructor()
      ->getMock();

    $plugin = $this->getMockForAbstractClass('Drupal\sapi\StatPluginMethodBase', array(
      $this->pluginConfig, $this->pluginId, $this->pluginDefinition, $entity_manager, $request
    ));

    // Test the \Drupal\sapi\StatMethodPluginBase::settings() method.
    $this->assertEquals($plugin->settings(), $this->pluginConfig);

    // Test the \Drupal\sapi\StatMethodPluginBase::getConfig() method.
    $this->assertEquals($plugin->getConfig(), $this->pluginConfig);
    $this->assertEquals($plugin->getConfig('status'), $this->pluginConfig['status']);
    $this->assertEquals($plugin->getConfig('does_not_exist'), FALSE);

    // Test the \Drupal\sapi\StatMethodPluginBase::getModuleConfig() method.
    $this->assertEquals($plugin->getModuleConfig(), $this->pluginConfig['settings']);
    $this->assertEquals($plugin->getModuleConfig('sapi'), $this->pluginConfig['settings']['sapi']);
    $this->assertEquals($plugin->getModuleConfig('does_not_exist'), array());
    $this->assertEquals($plugin->getModuleConfig('sapi', 'dnt'), $this->pluginConfig['settings']['sapi']['dnt']);
    $this->assertEquals($plugin->getModuleConfig('does_not_exist', 'dnt'), FALSE);
  }

  /**
   * Tests \Drupal\sapi\StatMethodPluginBase config setter methods.
   */
  public function testConfigSetters() {
    $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
    $entity_manager = $this->getMockBuilder('Drupal\Core\Entity\EntityManager')
      ->disableOriginalConstructor()
      ->getMock();

    $plugin = $this->getMockForAbstractClass('Drupal\sapi\StatPluginMethodBase', array(
      $this->pluginConfig, $this->pluginId, $this->pluginDefinition, $entity_manager, $request
    ));

    // Test the \Drupal\sapi\StatMethodPluginBase::setConfig() method.
    $plugin->setConfig('status', 0);
    $this->assertEquals($plugin->getConfig('status'), 0);

    // Test the \Drupal\sapi\StatMethodPluginBase::setModuleConfig() method.
    $plugin->setModuleConfig('sapi', 'dnt', 1);
    $this->assertEquals($plugin->getModuleConfig('sapi', 'dnt'), 1);
  }


  /**
   * Tests base restriction behavior.
   */
  public function testRestrictions() {
    $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
    $entity_manager = $this->getMockBuilder('Drupal\Core\Entity\EntityManager')
      ->disableOriginalConstructor()
      ->getMock();
    $headerbag = $this->getMock('Symfony\Component\HttpFoundation\HeaderBag');

    // Instantiate a plugin with no headers.
    $request->headers = $headerbag;
    $plugin = $this->getMockForAbstractClass('Drupal\sapi\StatPluginMethodBase', array(
      $this->pluginConfig, $this->pluginId, $this->pluginDefinition, $entity_manager, $request
    ));

    // Ensure that plugins are not restricted by "default".
    $this->assertEquals($plugin->restricted(), FALSE);

    // Ensure that plugins are restricted when "status" is 0.
    $plugin->setConfig('status', 0);
    $this->assertEquals($plugin->restricted(), TRUE);
    $plugin->setConfig('status', 1);

    // Ensure that requests are not restricted when DNT is respected but no DNT
    // header is passed in.
    $plugin->setModuleConfig('sapi', 'dnt', 1);
    $this->assertEquals($plugin->restricted(), FALSE);
    $plugin->setModuleConfig('sapi', 'dnt', 0);

    // Ensure restrictions don't have double-negative issues.
    $plugin->setModuleConfig('sapi', 'dnt', 1);
    $plugin->setConfig('status', 0);
    $this->assertEquals($plugin->restricted(), TRUE);
    $plugin->setModuleConfig('sapi', 'dnt', 0);
    $plugin->setConfig('status', 1);

    // Instantiate a plugin with a request including DNT headers.
    $headerbag_dnt = $this->getMockBuilder('Symfony\Component\HttpFoundation\HeaderBag')
      ->setConstructorArgs(array(array('dnt' => 1)))
      ->getMock();
    $request->headers = $headerbag_dnt;
    $plugin_dnt = $this->getMockForAbstractClass('Drupal\sapi\StatPluginMethodBase', array(
      $this->pluginConfig, $this->pluginId, $this->pluginDefinition, $entity_manager, $request
    ));

    // Ensure that HeaderBag::get('dnt') is called once.
    $headerbag_dnt
      ->expects($this->once())
      ->method('get')
      ->with('dnt')
      ->will($this->returnValue(1));

    // Ensure that plugins are restricted when DNT is respected.
    $plugin_dnt->setModuleConfig('sapi', 'dnt', 1);
    $this->assertEquals($plugin_dnt->restricted(), TRUE);
    $plugin_dnt->setModuleConfig('sapi', 'dnt', 0);

    // Ensure that requests with DNT headers treat status normally.
    $plugin_dnt->setConfig('status', 0);
    $this->assertEquals($plugin_dnt->restricted(), TRUE);
    $plugin_dnt->setConfig('status', 1);

    // Ensure that requests with DNT headers don't have double-negative issues.
    $plugin_dnt->setModuleConfig('sapi', 'dnt', 1);
    $plugin_dnt->setConfig('status', 0);
    $this->assertEquals($plugin_dnt->restricted(), TRUE);
    $plugin_dnt->setModuleConfig('sapi', 'dnt', 0);
    $plugin_dnt->setConfig('status', 1);
  }

  /**
   * Tests base collection behavior of creating a Stat entity.
   */
  public function testCollection() {
    $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
    $storage_controller = $this->getMockBuilder('Drupal\sapi\StatStorageController')
      ->disableOriginalConstructor()
      ->getMock();
    $entity = $this->getMockBuilder('Drupal\Core\Entity\Entity')
      ->disableOriginalConstructor()
      ->getMock();
    $entity_manager = $this->getMockBuilder('Drupal\Core\Entity\EntityManager')
      ->disableOriginalConstructor()
      ->getMock();

    // Ensure the entity manager's getStorageController() method is called once
    // with the argument "stat."
    $entity_manager
      ->expects($this->once())
      ->method('getStorageController')
      ->with('stat')
      ->will($this->returnValue($storage_controller));

    // Ensure the storage controller's create() method is called once.
    $storage_controller->expects($this->once())
      ->method('create')
      ->will($this->returnValue($entity));

    // Instantiate the plugin and call the collect() method.
    $plugin = $this->getMockForAbstractClass('Drupal\sapi\StatPluginMethodBase', array(
      $this->pluginConfig, $this->pluginId, $this->pluginDefinition, $entity_manager, $request
    ));
    $plugin->collect();
  }

  /**
   * Tests base data management behavior.
   */
  public function testDataManagement() {
    $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
    $storage_controller = $this->getMockBuilder('Drupal\sapi\StatStorageController')
      ->disableOriginalConstructor()
      ->getMock();
    $entity_manager_disabled = $this->getMockBuilder('Drupal\Core\Entity\EntityManager')
      ->disableOriginalConstructor()
      ->getMock();
    $entity_manager = clone $entity_manager_disabled;

    // Ensure that, when SAPI retention length is 0, no code is run.
    $entity_manager_disabled
      ->expects($this->never())
      ->method('getStorageController');

    $plugin = $this->getMockForAbstractClass('Drupal\sapi\StatPluginMethodBase', array(
      $this->pluginConfig, $this->pluginId, $this->pluginDefinition, $entity_manager, $request
    ));
    $plugin->manageData();

    // Ensure that, when SAPI retention length is more than 0, code is run.
    $entity_manager
      ->expects($this->any())
      ->method('getStorageController')
      ->with('stat')
      ->will($this->returnValue($storage_controller));

    // Ensure we try and load and delete entities by properties.
    $storage_controller
      ->expects($this->once())
      ->method('loadByConditionalProperties')
      ->with($this->arrayHasKey('method', 'created'))
      ->will($this->returnValue(array(1)));
    $entity_manager
      ->expects($this->once())
      ->method('delete');

    $plugin = $this->getMockForAbstractClass('Drupal\sapi\StatPluginMethodBase', array(
      $this->pluginConfig, $this->pluginId, $this->pluginDefinition, $entity_manager, $request
    ));
    $plugin->setModuleConfig('sapi', 'retention_length', 60);
    $plugin->manageData();
  }
}
