<?php

/**
 * @file
 * Contains Drupal\sapi\Plugin\StatDataAnnotatedClassDiscovery.
 */

namespace Drupal\sapi\Plugin;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Symfony\Component\DependencyInjection\Container;


/**
 * Overrides Core's default annotated class discovery mechanism to include
 * config data when providing a plugin's definition.
 */
class StatDataAnnotatedClassDiscovery extends AnnotatedClassDiscovery {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager;
   */
  protected $entityManager;

  /**
   * Constructs a StatMethodAnnotatedClassDiscovery object.
   *
   * @param string $subdir
   *   The plugin's subdirectory, for example Stat/method.
   * @param \Traversable $root_namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   *  \Plugin\$subdir will be appended to each namespace.
   * @param string $plugin_definition_annotation_name
   *   (optional) The name of the annotation that contains the plugin definition.
   *   Defaults to 'Drupal\Component\Annotation\Plugin'.
   */
  function __construct($subdir, \Traversable $root_namespaces, EntityManager $entity_manager, $plugin_definition_annotation_name = 'Drupal\Component\Annotation\Plugin') {
    $this->entityManager = $entity_manager;
    parent::__construct($subdir, $root_namespaces, $plugin_definition_annotation_name);
  }

  /**
   * Implements Drupal\Component\Plugin\Discovery\DiscoveryInterface::getDefinitions().
   */
  public function getDefinitions() {
    $definitions = parent::getDefinitions();
    foreach ($definitions as $id => &$definition) {
      $this->processDefinition($definition, $id);
    }
    return $definitions;
  }

  /**
   * Performs extra processing on plugin definitions. In particular, we ensure
   * that this data plugin can be written to on the storage backend.
   */
  public function processDefinition(&$definition, $plugin_id) {
    $this->entityManager->getStorageController('stat')->ensureProperty($plugin_id, $definition['schema']);
  }

}
