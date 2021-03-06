<?php

/**
 * @file
 * Contains \Drupal\sapi\Plugin\StatPluginManager.
 */

namespace Drupal\sapi\Plugin;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Component\Plugin\Discovery\DerivativeDiscoveryDecorator;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Plugin\Discovery\AlterDecorator;
use Drupal\Core\Plugin\Discovery\CacheDecorator;
use Drupal\Core\Plugin\Factory\ContainerFactory;

/**
 * Plugin type manager for all Statistics API plugins.
 */
class StatPluginManager extends PluginManagerBase {

  /**
   * Constructs a StatPluginManager object.
   *
   * @param string $type
   *   The plugin type, for example method.
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   
   */
  public function __construct($type, \Traversable $namespaces, EntityManager $entity_manager, ConfigFactory $config) {
    $subdir = 'Plugin/Stat/' . $type;
    $annotation_class = 'Drupal\sapi\Annotation\Stat' . ucfirst($type);

    // Instantiate the correct annotated class discovery class.
    switch ($type) {
      case 'method':
        $this->discovery = new StatMethodAnnotatedClassDiscovery($subdir, $namespaces, $entity_manager, $config, $annotation_class);
        break;

      case 'data':
        $this->discovery = new StatDataAnnotatedClassDiscovery($subdir, $namespaces, $entity_manager, $annotation_class);
        break;

      default:
        throw new StatPluginException('The provided plugin type is invalid.');
    }

    // Wrap our annotated class discovery class appropriately.
    $this->discovery = new DerivativeDiscoveryDecorator($this->discovery);
    $this->discovery = new AlterDecorator($this->discovery, 'sapi_' . $type . '_info');
    $this->discovery = new CacheDecorator($this->discovery, 'sapi:' . $type, 'cache');

    $this->factory = new ContainerFactory($this);
  }

}
