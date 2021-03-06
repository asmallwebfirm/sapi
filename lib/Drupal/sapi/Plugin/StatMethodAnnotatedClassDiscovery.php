<?php

/**
 * @file
 * Contains Drupal\sapi\Plugin\StatMethodAnnotatedClassDiscovery.
 */

namespace Drupal\sapi\Plugin;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\DependencyInjection\Container;


/**
 * Overrides Core's default annotated class discovery mechanism to include
 * config data when providing a plugin's definition.
 */
class StatMethodAnnotatedClassDiscovery extends AnnotatedClassDiscovery {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager;
   */
  protected $entityManager;

  /**
   * The config factory for this plugin manager.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs a StatMethodAnnotatedClassDiscovery object.
   *
   * @param string $subdir
   *   The plugin's subdirectory, for example Stat/method.
   * @param \Traversable $root_namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   *  \Plugin\$subdir will be appended to each namespace.
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   The config factory service.
   * @param array $annotation_namespaces
   *   (optional) The namespaces of classes that can be used as annotations.
   *   Defaults to an empty array.
   * @param string $plugin_definition_annotation_name
   *   (optional) The name of the annotation that contains the plugin definition.
   *   Defaults to 'Drupal\Component\Annotation\Plugin'.
   */
  function __construct($subdir, \Traversable $root_namespaces, EntityManager $entity_manager, ConfigFactory $config, $plugin_definition_annotation_name = 'Drupal\Component\Annotation\Plugin') {
    $this->configFactory = $config;
    $this->entityManager = $entity_manager;
    parent::__construct($subdir, $root_namespaces, $plugin_definition_annotation_name);
  }

  /**
   * Implements Drupal\Component\Plugin\Discovery\DiscoveryInterface::getDefinitions().
   */
  public function getDefinitions() {
    $definitions = parent::getDefinitions();
    $config_definitions = $this->getDefinitionsFromConfig(array_keys($definitions));
    foreach ($definitions as $id => &$definition) {
      if (isset($config_definitions[$id])) {
        $definition = array_merge($definition, get_object_vars($config_definitions[$id]));
      }
    }
    return $definitions;
  }

  /**
   * Loads configurations for all defined methods.
   *
   * @param $ids
   *   An array of entity IDs, or NULL to load all entities.
   *
   * @return array
   *   An array of config entities.
   */
  protected function getDefinitionsFromConfig($ids = array()) {
    $entity_type = 'stat_method';
    $statMethod = $this->entityManager->getDefinition($entity_type);
    $config_class = $statMethod->getClass();
    $prefix = $statMethod->getConfigPrefix() . '.';
    $id_key = $statMethod->getKey('id');

    // Get the names of the configuration entities we are going to load.
    $names = array();
    foreach ($ids as $id) {
      // Add the prefix to the ID to serve as the configuration object name.
      $names[] = $prefix . $id;
    }

    // Load all of the configuration entities.
    $result = array();
    foreach ($this->configFactory->loadMultiple($names) as $config) {
      $result[$config->get($id_key)] = new $config_class($config->get(), $entity_type);
    }

    return $result;
  }

}
