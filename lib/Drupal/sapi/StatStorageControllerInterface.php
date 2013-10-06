<?php

/**
 * @file
 * Contains \Drupal\sapi\StatStorageControllerInterface.
 */

namespace Drupal\sapi;

/**
 * Provides an interface defining a stat storage controller.
 */
interface StatStorageControllerInterface {

  /**
   * Returns a list of entities that match any number of entity property
   * conditions with arbitrary operators.
   *
   * @param array $values
   *   An array of arrays whose outer keys are entity properties and whose inner
   *   keys are "value" (required, the value to be tested for) and optionally
   *   "op" for the operator to be used in building the condition.
   *
   * @return array
   *   An array of entities, keyed by their IDs.
   */
  public function loadByConditionalProperties(array $values);

  /**
   * Ensures that a given property (e.g. a Stat Data plugin) can be written
   * on this storage backend.
   *
   * @param string $property
   *   The ID of the property (corresponding to the ID of the plugin).
   * @param array $schema
   *   The Schema API field definition for this data property.
   *
   * @see \Drupal\sapi\Plugin\StatDataPluginManager::processDefinition()
   */
  public function ensureProperty($property, array $schema);

  /**
   * Ensures that a given property (e.g. a Stat Data plugin) is not available
   * on this storage backend (e.g. does not exist). This is the opposite of the
   * ensureProperty method.
   *
   * @param string $property
   */
  public function ensureNoProperty($property);

}
