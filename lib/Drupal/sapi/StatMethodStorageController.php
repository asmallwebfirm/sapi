<?php

/**
 * @file
 * Definition of Drupal\sapi\StatMethodStorageController.
 */

namespace Drupal\sapi;

use Drupal\Core\Entity\EntityStorageControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Component\Utility\String;


/**
 * Defines the Stat Method entity storage controller class.
 */
class StatMethodStorageController extends EntityStorageControllerBase {

  /**
   * Whether this entity type should use the static cache.
   *
   * Set by entity info.
   *
   * @var boolean
   */
  protected $cache;

  /**
   * Statistics method plugin manager.
   *
   * @var \Drupal\sapi\Plugin\StatPluginManager
   */
  protected $manager;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The config storage service.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQueryFactory;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, $entity_type, array $entity_info) {
    return new static(
      $entity_type,
      $entity_info,
      $container->get('plugin.manager.sapi.method'),
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('entity.query')
    );
  }

  /**
   * Constructs a StatMethodStorageController object.
   *
   * @param string $entity_type
   *   The entity type for which the instance is created.
   * @param array $entity_info
   *   An array of entity info for the entity type.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The plugin manager to be used.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The config storage service.
   */
  public function __construct($entity_type, array $entity_info, PluginManagerInterface $manager, ConfigFactory $config_factory, ModuleHandlerInterface $module_handler, QueryFactory $entity_query_factory) {
    parent::__construct($entity_type, $entity_info);

    $this->manager = $manager;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->entityQueryFactory = $entity_query_factory;

    // Check if the entity type supports IDs.
    if (isset($this->entityInfo['entity_keys']['id'])) {
      $this->idKey = $this->entityInfo['entity_keys']['id'];
    }
    else {
      $this->idKey = FALSE;
    }

    $this->uuidKey = FALSE;
    $this->revisionKey = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids = NULL) {
    $entities = array();

    // Create a new variable which is either a prepared version of the $ids
    // array for later comparison with the entity cache, or FALSE if no $ids
    // were passed. The $ids array is reduced as items are loaded from cache,
    // and we need to know if it's empty for this reason to avoid querying the
    // database when all requested entities are loaded from cache.
    $passed_ids = !empty($ids) ? array_flip($ids) : FALSE;
    // Try to load entities from the static cache, if the entity type supports
    // static caching.
    if ($this->cache && $ids) {
      $entities += $this->cacheGet($ids);
      // If any entities were loaded, remove them from the ids still to load.
      if ($passed_ids) {
        $ids = array_keys(array_diff_key($passed_ids, $entities));
      }
    }

    // Build any remaining entities from the plugin manager. This is the case if
    // $ids is set to NULL (so we load all entities) or if there are any ids
    // left to load.
    if ($ids === NULL || $ids) {
      // Loop through all provided plugins and generate entities.
      $built_entities = array();
      $plugins = $this->manager->getDefinitions();
      foreach ($plugins as $id => $definition) {
        $built_entities[$id] = $this->create($definition);
      }
    }

    // Pass entities built from the plugin manager through $this->attachLoad(),
    // which calls the entity type specific load callback.
    if (!empty($built_entities)) {
      $this->attachLoad($built_entities);
      $entities += $built_entities;
    }

    if ($this->cache) {
      // Add entities to the cache.
      if (!empty($built_entities)) {
        $this->cacheSet($built_entities);
      }
    }

    // Ensure that the returned array is ordered the same as the original
    // $ids array if this was passed in and remove any invalid ids.
    if ($passed_ids) {
      // Remove any invalid ids from the array.
      $passed_ids = array_intersect_key($passed_ids, $entities);
      foreach ($entities as $entity) {
        $passed_ids[$entity->id()] = $entity;
      }
      $entities = $passed_ids;
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    $entities = $this->loadMultiple(array($id));
    return isset($entities[$id]) ? $entities[$id] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function loadRevision($revision_id) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteRevision($revision_id) {
    return NULL;
  }

  /**
   * Implements \Drupal\Core\Entity\EntityStorageControllerInterface::loadByProperties().
   */
  public function loadByProperties(array $values = array()) {
    $entities = $this->loadMultiple();

    foreach ($values as $key => $value) {
      $entities = array_filter($entities, function($entity) use ($key, $value) {
        return $value === $entity->get($key);
      });
    }

    return $entities;
  }

  /**
   * Attaches data to entities upon loading.
   *
   * This will attach fields, if the entity is fieldable. It calls
   * hook_entity_load() for modules which need to add data to all entities.
   * It also calls hook_TYPE_load() on the loaded entities. For example
   * hook_node_load() or hook_user_load(). If your hook_TYPE_load()
   * expects special parameters apart from the queried entities, you can set
   * $this->hookLoadArguments prior to calling the method.
   * See Drupal\node\NodeStorageController::attachLoad() for an example.
   *
   * @param $queried_entities
   *   Associative array of query results, keyed on the entity ID.
   * @param $load_revision
   *   (optional) TRUE if the revision should be loaded, defaults to FALSE.
   */
  protected function attachLoad(&$built_entities, $load_revision = FALSE) {
    // Call hook_entity_load().
    foreach ($this->moduleHandler->getImplementations('entity_load') as $module) {
      $function = $module . '_entity_load';
      $function($built_entities, $this->entityType);
    }

    // Call hook_TYPE_load(). The first argument for hook_TYPE_load() are
    // always the queried entities, followed by additional arguments set in
    // $this->hookLoadArguments.
    $args = array_merge(array($built_entities), $this->hookLoadArguments);
    foreach ($this->moduleHandler->getImplementations($this->entityType . '_load') as $module) {
      call_user_func_array($module . '_' . $this->entityType . '_load', $args);
    }
  }

  /**
   * Implements \Drupal\Core\Entity\EntityStorageControllerInterface::create().
   */
  public function create(array $values) {
    $entity_class = $this->entityInfo['class'];
    $entity_class::preCreate($this, $values);

    $entity = new $entity_class($values, $this->entityType);

    $entity->postCreate($this);

    // Modules might need to add or change the data initially held by the new
    // entity object, for instance to fill-in default values.
    $this->invokeHook('create', $entity);

    return $entity;
  }

  /**
   * Implements \Drupal\Core\Entity\EntityStorageControllerInterface::create().
   */
  public function delete(array $entities) {
    if (!$entities) {
      // If no IDs or invalid IDs were passed, do nothing.
      return;
    }
    else {
      // We can't delete something that's in code.
      $e = new EntityStorageException(String::format('Cannot delete an entity of type %type.', array('%type' => $this->entityType)));
      watchdog_exception($this->entityType, $e);
      throw new EntityStorageException($e->getMessage());
    }
  }

  /**
   * Implements \Drupal\Core\Entity\EntityStorageControllerInterface::save().
   */
  public function save(EntityInterface $entity) {
    $prefix = $this->getConfigPrefix();

    // Configuration entity IDs are strings, and '0' is a valid ID.
    $id = $entity->id();
    if ($id === NULL || $id === '') {
      throw new EntityMalformedException('The entity does not have an ID.');
    }

    // Load the stored entity, if any.
    // At this point, the original ID can only be NULL or a valid ID.
    if ($entity->getOriginalID() !== NULL) {
      $id = $entity->getOriginalID();
    }
    $config = $this->configFactory->get($prefix . $id);
    $is_new = $config->isNew();

    $this->resetCache(array($id));
    $entity->original = $this->load($id);

    $entity->preSave($this);
    $this->invokeHook('presave', $entity);

    // Retrieve the desired properties and set them in config.
    foreach ($entity->getExportProperties() as $key => $value) {
      $config->set($key, $value);
    }

    $config->save();
    $entity->postSave($this, TRUE);
    $this->invokeHook('update', $entity);

    // Immediately update the original ID.
    $entity->setOriginalID($entity->id());

    unset($entity->original);

    // Ensure the plugin manager has the most up-to-date Stat Methods.
    $this->manager->clearCachedDefinitions();

    return SAVED_UPDATED;
  }

  /**
   * {@inheritdoc}
   */
  public function baseFieldDefinitions() {
    return array();
  }

  /**
   * Returns the config prefix used by the configuration entity type.
   *
   * @return string
   *   The full configuration prefix, for example 'views.view.'.
   */
  public function getConfigPrefix() {
    return $this->entityInfo['config_prefix'] . '.';
  }

  /**
   * Returns an entity query instance.
   *
   * @param string $conjunction
   *   - AND: all of the conditions on the query need to match.
   *   - OR: at least one of the conditions on the query need to match.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The query instance.
   *
   * @see \Drupal\Core\Entity\EntityStorageControllerInterface::getQueryServicename()
   */
  public function getQuery($conjunction = 'AND') {
    return $this->entityQueryFactory->get($this->entityType, $conjunction);
  }

  /**
   * Implements Drupal\Core\Entity\EntityStorageControllerInterface::getQueryServicename().
   */
  public function getQueryServicename() {
    return 'entity.query.config';
  }

  /**
   * Invokes a hook on behalf of the entity.
   *
   * @param $hook
   *   One of 'presave', 'insert', 'update', 'predelete', 'delete', or
   *  'revision_delete'.
   * @param $entity
   *   The entity object.
   */
  protected function invokeHook($hook, EntityInterface $entity) {
    // Invoke the hook.
    $this->moduleHandler->invokeAll($this->entityType . '_' . $hook, array($entity));
    // Invoke the respective entity-level hook.
    $this->moduleHandler->invokeAll('entity_' . $hook, array($entity, $this->entityType));
  }

}
