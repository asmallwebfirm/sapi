<?php

/**
 * @file
 * Definition of Drupal\sapi\StatStorageController.
 */

namespace Drupal\sapi;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\DatabaseStorageController;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Component\Uuid\UuidInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller class for Stats.
 *
 * This extends the Drupal\Core\Entity\DatabaseStorageController class, adding
 * required special handling for Stat entities.
 */
class StatStorageController extends DatabaseStorageController implements StatStorageControllerInterface {

  /**
   * The Entity Query factory service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $query_factory;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, $entity_type, array $entity_info) {
    return new static(
      $entity_type,
      $entity_info,
      $container->get('database'),
      $container->get('uuid'),
      $container->get('entity.query')
    );
  }

  /**
   * Constructs a StatDataStorageController object.
   *
   * @param string $entity_type
   *   The entity type for which the instance is created.
   * @param array $entity_info
   *   An array of entity info for the entity type.
   * @param \Drupal\Core\Database\Connection
   *   The Database connection to be used.
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The Entity Query factory service to be used.
   */
  public function __construct($entity_type, array $entity_info, Connection $connection, UuidInterface $uuid_service, QueryFactory $query_factory) {
    parent::__construct($entity_type, $entity_info, $connection, $uuid_service);
    $this->query_factory = $query_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function loadByConditionalProperties(array $values) {
    $entity_query = $this->query_factory->get($this->entityType, 'AND');
    foreach ($values as $property => $condition) {
      $operator = isset($condition['op']) ? $condition['op'] : NULL;
      $entity_query->condition($property, $condition['value'], $operator);
    }

    $entities = $entity_query->execute();
    return $entities ? $this->loadMultiple($entities) : array();
  }

  /**
   * {@inheritdoc}
   */
  public function ensureProperty($property, array $schema) {
    // If the given property does not exist, add it.
    if (!$this->database->schema()->fieldExists('stat', $property)) {
      $this->database->schema()->addField('stat', $property, $schema);

      // Wrapper for watchdog exists (for unit testing).
      // @todo Remove when watchdog is an injectable service, or similar.
      if (defined('WATCHDOG_NOTICE')) {
        watchdog('sapi', 'Added the %data property to the Stat schema.', array(
          '%data' => $property,
          ), WATCHDOG_NOTICE);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function ensureNoProperty($property) {
    if ($this->database->schema()->fieldExists('stat', $property)) {
      $this->database->schema()->dropField('stat', $property);

      // Wrapper for watchdog exists (for unit testing).
      // @todo Remove when watchdog is an injectable service, or similar.
      if (defined('WATCHDOG_NOTICE')) {
        watchdog('sapi', 'Removed all data for the %data property and removed it from the Stat schema.', array(
          '%data' => $property,
        ), WATCHDOG_NOTICE);
      }
    }
  }

}
