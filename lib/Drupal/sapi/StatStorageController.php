<?php

/**
 * @file
 * Definition of Drupal\node\NodeStorageController.
 */

namespace Drupal\sapi;

use Drupal\Core\Entity\DatabaseStorageControllerNG;
use Drupal\Core\Entity\EntityInterface;

/**
 * Controller class for Stats.
 *
 * This extends the Drupal\Core\Entity\DatabaseStorageController class, adding
 * required special handling for Stat entities.
 */
class StatStorageController extends DatabaseStorageControllerNG {

  /**
   * Overrides \Drupal\Core\Entity\DataBaseStorageControllerNG::basePropertyDefinitions().
   */
  public function baseFieldDefinitions() {
    $properties['sid'] = array(
      'label' => t('Stat ID'),
      'description' => t('The stat ID.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $properties['method'] = array(
      'label' => t('Method'),
      'description' => t('The stat method.'),
      'type' => 'string_field',
      'read-only' => TRUE,
    );
    $properties['created'] = array(
      'label' => t('Created'),
      'description' => t('The time that the stat was created.'),
      'type' => 'integer_field',
    );

    return $properties;
  }

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
  public function loadByConditionalProperties(array $values) {
    $entity_query = \Drupal::entityQuery($this->entityType);
    foreach ($values as $property => $condition) {
      $operator = isset($condition['op']) ? $condition['op'] : NULL;
      $entity_query->condition($property, $condition['value'], $operator);
    }

    $entities = $entity_query->execute();
    return $entities ? $this->loadMultiple($entities) : array();
  }

}
