<?php

/**
 * @file
 * Contains \Drupal\sapi\Plugin\StatPluginManager.
 */

namespace Drupal\sapi\Plugin;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Component\Plugin\Discovery\DerivativeDiscoveryDecorator;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Component\Plugin\Discovery\ProcessDecorator;
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
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   
   */
  public function __construct($type, \Traversable $namespaces, PluginManagerInterface $entityManager, ConfigFactory $config) {
    $this->discovery = new StatAnnotatedClassDiscovery('Stat/' . $type, $namespaces, $entityManager, $config);
    $this->discovery = new DerivativeDiscoveryDecorator($this->discovery);
    $this->discovery = new AlterDecorator($this->discovery, 'sapi_plugins_' . $type);
    $this->discovery = new CacheDecorator($this->discovery, 'sapi:' . $type, 'cache');

    $this->factory = new ContainerFactory($this);

    $this->defaults += array(
      'parent' => 'parent',
      'plugin_type' => $type,
      'module' => 'sapi',
      'register_theme' => FALSE,
    );
  }

}
