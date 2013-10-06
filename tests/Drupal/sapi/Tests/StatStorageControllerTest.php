<?php

/**
 * @file
 * PHPUnit tests for \Drupal\sapi\StatStorageController.
 */

namespace Drupal\sapi\Tests;

use Drupal\sapi\StatStorageController;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the default Stat storage controller.
 *
 * @group SAPI
 */
class StatStorageControllerTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Statistics storage controller test',
      'description' => 'Unit tests for the default stat storage controller.',
      'group' => 'sapi'
    );
  }

  /**
   * Default values to pass to loadByConditionalProperties().
   */
  protected $condition_values = array(
    'prop1' => array('value' => NULL),
    'prop2' => array('value' => NULL),
  );

  /**
   * Default entity info for the Stat entity.
   */
  protected $entity_info = array(
    'class' => 'Drupal\sapi\Entity\Stat',
  );

  /**
   * Tests \Drupal\sapi\StatStorageController::loadByConditionalProperties().
   */
  public function testloadByConditionalProperties() {
    // Stub database connection.
    $db = $this->getMockBuilder('Drupal\Core\Database\Connection')
      ->disableOriginalConstructor()
      ->getMock();
    $field = $this->getMockBuilder('Drupal\field\FieldInfo')
      ->disableOriginalConstructor()
      ->getMock();
    $uuid = $this->getMock('Drupal\Component\Uuid\UuidInterface');
    $eq = $this->getMockBuilder('Drupal\Core\Entity\Query\QueryFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $query = $this->getMock('Drupal\Core\Entity\Query\QueryInterface');

    // We expect QueryInterface::condition() to be called a number of times.
    $query->expects($this->exactly(count($this->condition_values)))
      ->method('condition')
      ->withAnyParameters();

    // We expect QueryInterface::execute() to be executed once.
    $query->expects($this->once())
      ->method('execute')
      ->will($this->returnValue(array('not empty')));

    // We expect QueryFactory::get() to get be called.
    $eq->expects($this->once())
      ->method('get')
      ->with('stat')
      ->will($this->returnValue($query));

    // Instantiate a mock of our StatStorageController.
    $controller = $this->getMockBuilder('Drupal\sapi\StatStorageController')
      ->setConstructorArgs(array('stat', $this->entity_info, $db, $field, $uuid, $eq))
      ->setMethods(array('loadMultiple'))
      ->getMock();

    // Stub the loadMultiple() to return something.
    $return = array(1);
    $controller->expects($this->once())
      ->method('loadMultiple')
      ->will($this->returnValue($return));

    // Check the return of this method.
    $this->assertEquals($return, $controller->loadByConditionalProperties($this->condition_values));
  }

  /**
   * Tests \Drupal\sapi\StatStorageController::ensureProperty().
   */
  public function testEnsureProperty() {
    $plugin_id = key($this->condition_values);
    $plugin_schema = $this->condition_values[$plugin_id];

    // Stub database connection.
    $db = $this->getMockBuilder('Drupal\Core\Database\Connection')
      ->disableOriginalConstructor()
      ->getMock();
    $field = $this->getMockBuilder('Drupal\field\FieldInfo')
      ->disableOriginalConstructor()
      ->getMock();
    $uuid = $this->getMock('Drupal\Component\Uuid\UuidInterface');
    $eq = $this->getMockBuilder('Drupal\Core\Entity\Query\QueryFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $schema = $this->getMockBuilder('Drupal\Core\Database\Schema')
      ->disableOriginalConstructor()
      ->getMock();

    // Ensure fieldExists is called, provide its value.
    $schema->expects($this->atLeastOnce())
      ->method('fieldExists')
      ->with('stat', $plugin_id)
      ->will($this->returnValue(FALSE));

    // Ensure addField is called once.
    $schema->expects($this->once())
      ->method('addField')
      ->with('stat', $plugin_id, $plugin_schema);

    // Ensure we're getting the schema (doesn't matter how many times).
    $db->expects($this->atLeastOnce())
      ->method('schema')
      ->will($this->returnValue($schema));

    // Instantiate and test the method.
    $controller = new StatStorageController('stat', $this->entity_info, $db, $field, $uuid, $eq);
    $controller->ensureProperty($plugin_id, $plugin_schema);
  }

  /**
   * Tests \Drupal\sapi\StatStorageController::ensureNoProperty().
   */
  public function testEnsureNoProperty() {
    $plugin_id = key($this->condition_values);
    $plugin_schema = $this->condition_values[$plugin_id];

    // Stub database connection.
    $db = $this->getMockBuilder('Drupal\Core\Database\Connection')
      ->disableOriginalConstructor()
      ->getMock();
    $field = $this->getMockBuilder('Drupal\field\FieldInfo')
      ->disableOriginalConstructor()
      ->getMock();
    $uuid = $this->getMock('Drupal\Component\Uuid\UuidInterface');
    $eq = $this->getMockBuilder('Drupal\Core\Entity\Query\QueryFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $schema = $this->getMockBuilder('Drupal\Core\Database\Schema')
      ->disableOriginalConstructor()
      ->getMock();

    // Ensure fieldExists is called, provide its value.
    $schema->expects($this->atLeastOnce())
      ->method('fieldExists')
      ->with('stat', $plugin_id)
      ->will($this->returnValue(TRUE));

    // Ensure dropField is called once.
    $schema->expects($this->once())
      ->method('dropField')
      ->with('stat', $plugin_id);

    // Ensure we're getting the schema (doesn't matter how many times).
    $db->expects($this->atLeastOnce())
      ->method('schema')
      ->will($this->returnValue($schema));

    // Instantiate and test the method.
    $controller = new StatStorageController('stat', $this->entity_info, $db, $field, $uuid, $eq);
    $controller->ensureNoProperty($plugin_id);
  }
}
