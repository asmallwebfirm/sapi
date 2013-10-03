<?php

/**
 * @file
 * Contains \Drupal\sapi\StatMethodFormController.
 */

namespace Drupal\sapi;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityFormController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for statistic method forms.
 */
class StatMethodFormController extends EntityFormController implements ContainerInjectionInterface {

  /**
   * The plugin manager service.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * The statistics method plugin.
   *
   * @var \Drupal\sapi\StatPluginMethodInterface
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('plugin.manager.sapi.method')
    );
  }

  /**
   * Constructs a StatMethodFormController object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler service.
   * @param \Drupal\Component\Plugin\PluginManagerInterface
   *   The statistics method plugin manager.
   */
  public function __construct(ModuleHandlerInterface $module_handler, PluginManagerInterface $plugin_manager) {
    $this->moduleHandler = $module_handler;
    $this->pluginManager = $plugin_manager;
  }

  /**
   * On form initialization, pre-load the plugin for this statistics method.
   */
  protected function init(array &$form_state) {
    parent::init($form_state);
    $this->plugin = $this->pluginManager->createInstance($this->getEntity()->id());
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    drupal_set_title(t('Edit %label statistics method', array('%label' => $this->getEntity()->label())), PASS_THROUGH);

    $form = parent::form($form, $form_state);
    $form += $this->plugin->form($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, array &$form_state) {
    $actions = parent::actions($form, $form_state);
    unset($actions['delete']);
    $actions['submit']['#value'] = t('Save statistics method');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, array &$form_state) {
    parent::validate($form, $form_state);
    $this->plugin->validate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    parent::submit($form, $form_state);
    $this->plugin->submit($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    $plugin_settings = $this->plugin->settings();
    $method = $this->getEntity();
    $method->id = trim($method->id());
    $method->label = trim($method->label());
    $method->settings = $plugin_settings['settings'];
    $status = $method->save();

    if ($status == SAVED_UPDATED) {
      drupal_set_message(t('The statistics method %label has been updated.', array('%label' => $method->label())));
      $form_state['redirect'] = 'admin/config/statistics/methods';
    }
    else {
      drupal_set_message(t('There was a problem saving changes to the %label statistics method.', array('%label' => $method->label())), 'error');
    }
  }

}
