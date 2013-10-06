<?php

/**
 * @file
 * Contains \Drupal\sapi\StatDataPluginBase.
 */

namespace Drupal\sapi;

use Drupal\Component\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;


/**
 * Defines a base Stat data implementation that most data plugins will extend.
 *
 * This abstract class provides the generic statistics data configuration form,
 * default statistics data settings, and general handling for most uses.
 */
abstract class StatDataPluginBase extends PluginBase implements StatDataPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configuration += $this->getPluginDefinition();
  }

  /**
   * {@inheritdoc}
   */
  public function settings() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, array &$form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {

  }

}
