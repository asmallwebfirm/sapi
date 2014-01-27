<?php

/**
 * @file
 * Contains \Drupal\sapi\StatPluginMethodBase.
 */

namespace Drupal\sapi;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Entity\EntityStorageException;


/**
 * Defines a base statistics method implementation that most method plugins will
 * extend.
 *
 * This abstract class provides the generic statistics method configuration
 * form, default statistics method settings, and general handling for most uses.
 */
abstract class StatPluginMethodBase extends PluginBase implements StatPluginMethodInterface, ContainerFactoryPluginInterface {

  /**
   * The entity plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $entityManager;

  /**
   * The Request context in which this plugin is being called.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The string translation service.
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, PluginManagerInterface $entityManager, Request $request, TranslationInterface $stringTranslation) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->configuration += $this->getPluginDefinition();
    $this->request = $request;
    $this->entityManager = $entityManager;
    $this->stringTranslation = $stringTranslation;
  }

  /**
   * Implements \Drupal\Core\Plugin\ContainerFactoryPluginInterface::create().
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager'),
      $container->get('request'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    // Check for restrictions.
    if (!$this->restricted()) {
      $this->preCollect();
      $this->collect();
      $this->postCollect();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function restricted() {
    // Go no further if the method isn't even enabled.
    if (!$this->getConfig('status')) {
      return TRUE;
    }

    // Respect the "Do Not Track" header if configured to do so.
    if ($this->getModuleConfig('sapi', 'dnt') && $this->request->headers->get('dnt', FALSE)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function preCollect() {}

  /**
   * The default data collection behavior for Stat Method plugins is to create
   * and save a Stat entity whose bundle corresponds to the ID of this plugin.
   */
  public function collect() {
    $values = array('method' => $this->getPluginId());
    $stat = $this->entityManager->getStorageController('stat')->create($values);
    $stat->save();
  }

  /**
   * {@inheritdoc}
   */
  public function postCollect() {}

  /**
   * Default data management behavior for Stat Method plugins is to delete data
   * older than the specified interval.
   */
  public function manageData() {
    // Ignore if data retention is indefinite.
    if ($retention = $this->getModuleConfig('sapi', 'retention_length')) {
      // Query the storage controller for stat entities older than $retention.
      $time = REQUEST_TIME - $retention;
      $entities = $this->entityManager->getStorageController('stat')->loadByConditionalProperties(array(
        'method' => array('value' => $this->getPluginId()),
        'created' => array('value' => $time, 'op' => '<'),
      ));

      // Delete the queried entities.
      try {
        $this->entityManager->getStorageController('stat')->delete($entities);
        // Wrapper for watchdog exists (for unit testing).
        // @todo Remove when watchdog is an injectable service, or similar.
        if (defined('WATCHDOG_NOTICE')) {
          $settings = $this->settings();
          watchdog('sapi', 'Successfully removed !num %method stats.', array(
            '!num' => count($entities),
            '%method' => $settings['label'],
          ), WATCHDOG_NOTICE);
        }
      }
      catch (EntityStorageException $e) {
        // Wrapper for watchdog exists (for unit testing).
        // @todo Remove when watchdog is an injectable service, or similar.
        if (defined('WATCHDOG_ERROR')) {
          $settings = $this->settings();
          watchdog('sapi', 'Failed to delete %method stat data with message: !message', array(
             '%method' => $settings['label'],
             '!message' => $e->getMessage(),
          ), WATCHDOG_ERROR);
        }
      }
    }
  }

  /**
   * Returns plugin-specific settings for the statistics method.
   *
   * Statistics method plugins only need to override this method if they
   * override the defaults provided in StatPluginMethodBase::settings().
   *
   * @return array
   *   An array of statistics method-specific settings to override the defaults
   *   provided in StatPluginMethodBase::settings().
   *
   * @see \Drupal\sapi\StatPluginMethodBase::settings().
   */
  public function settings() {
    return $this->configuration;
  }

  /**
   * Implements \Drupal\sapi\StatPluginMethodInterface::form().
   *
   * Creates a generic configuration form for all statistics methods. Individual
   * method plugins can add elements to this form by overriding
   * StatPluginMethodBase::methodForm(). Most method plugins should not override
   * this method unless they need to alter the generic form elements.
   *
   * @see \Drupal\sapi\StatPluginMethodBase::methodForm()
   */
  public function form(array $form, array &$form_state) {
    $config = $this->getConfig();

    // Primary settings tab.
    $form['settings'] = array(
      '#attached' => array(
        'js' => array(drupal_get_path('module', 'sapi') . '/js/sapi.admin.js'),
      ),
      '#type' => 'vertical_tabs',
      '#parents' => array('settings'),
    );

    // Global stat method configuration configurations.
    $form['settings']['basic'] = array(
      '#type' => 'details',
      '#title' => $this->t('Basic details'),
      '#collapsed' => TRUE,
      '#group' => 'settings',
      '#weight' => 0,
    );

    $form['settings']['basic']['label'] = array(
      '#title' => $this->t('Label'),
      '#type' => 'textfield',
      '#default_value' => $config['label'],
      '#description' => $this->t('The human-readable name of this stat method.'),
      '#required' => TRUE,
      '#size' => 30,
    );

    $form['settings']['basic']['status'] = array(
      '#title' => $this->t('%method data collection enabled', array('%method' => $config['label'])),
      '#type' => 'checkbox',
      '#default_value' =>  $config['status'],
    );

    $form['settings']['basic']['description'] = array(
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' =>  $config['description'],
      '#description' => $this->t('Describe this statistics method.'),
    );

    // Global stat method restriction configurations.
    $form['settings']['restrictions'] = array(
      '#type' => 'details',
      '#title' => $this->t('Restrictions'),
      '#group' => 'settings',
    );

    $form['settings']['restrictions']['dnt'] = array(
      '#title' => $this->t('Respect the "Do Not Track" header'),
      '#type' => 'checkbox',
      '#default_value' => $this->getModuleConfig('sapi', 'dnt'),
    );

    // Global stat method data management configurations.
    $form['settings']['management'] = array(
      '#type' => 'details',
      '#title' => $this->t('Data management'),
      '#group' => 'settings',
    );

    $form['settings']['management']['retention_length'] = array(
      '#title' => $this->t('Data retention'),
      '#type' => 'select',
      '#options' => drupal_map_assoc(array(3600, 21600, 43200, 86400, 604800, 1209600, 2592000, 5184000, 15552000, 31536000), 'format_interval') + array(0 => $this->t('Indefinite')),
      '#default_value' => (int) $this->getModuleConfig('sapi', 'retention_length'),
      '#description' => $this->t('Data older than this interval will be automatically discarded.'),
    );

    // Add plugin-specific settings for this statistics method.
    $form += $this->methodForm($form, $form_state);
    return $form;
  }

  /**
   * Returns configuration form elements specific to this stats method plugin.
   *
   * Statistics methods that need to add form elements to the normal method
   * configuration form should implement this method.
   *
   * @param array $form
   *   The form definition array for the statistics method configuration form.
   * @param array $form_state
   *   An array containing the current state of the configuration form.
   *
   * @return array $form
   *   The renderable form array representing the entire configuration form.
   *
   * @see \Drupal\sapi\StatPluginMethodBase::form()
   */
  public function methodForm($form, &$form_state) {
    return array();
  }

  /**
   * Implements \Drupal\sapi\StatPluginMethodInterface::validate().
   *
   * Most statistics method plugins should not override this method. To add
   * validation for a specific method, override
   * StatPluginMethodBase::methodValdiate().
   *
   * @see \Drupal\sapi\StatPluginMethodBase::methodValidate()
   */
  public function validate(array $form, array &$form_state) {
    $this->methodValidate($form, $form_state);
  }

  /**
   * Adds statistics method-specific validation for the method form.
   *
   * Note that this takes the form structure and form state arrays for the full
   * statistics method configuration form as arguments, not just the elements
   * defined in StatPluginMethodBase::methodForm().
   *
   * @param array $form
   *   The form definition array for the full statistics method config form.
   * @param array $form_state
   *   An array containing the current state of the configuration form.
   *
   * @see \Drupal\sapi\StatPluginMethodBase::methodForm()
   * @see \Drupal\sapi\StatPluginMethodBase::methodSubmit()
   * @see \Drupal\sapi\StatPluginMethodBase::validate()
   */
  public function methodValidate($form, &$form_state) {}

  /**
   * Implements \Drupal\sapi\StatPluginMethodInterface::submit().
   *
   * Most statistics method plugins should not override this method. To add
   * submission handling for a specific statistics method, override
   * StatPluginMethodBase::methodSubmit().
   *
   * @see \Drupal\sapi\StatPluginMethodBase::methodSubmit()
   */
  public function submit(array $form, array &$form_state) {
    if (!form_get_errors()) {
      // Save off our configurations not explicitly defined elsewhere.
      $this->setModuleConfig('sapi', 'dnt', $form_state['values']['dnt']);
      $this->setModuleConfig('sapi', 'retention_length', $form_state['values']['retention_length']);

      $this->methodSubmit($form, $form_state);
    }
  }

  /**
   * Adds statistics method-specific submission handling for the method form.
   *
   * Note that this takes the form structure and form state arrays for the full
   * statistics method configuration form as arguments, not just the elements
   * defined in StatPluginMethodBase::methodForm().
   *
   * @param array $form
   *   The form definition array for the full statistics method config form.
   * @param array $form_state
   *   An array containing the current state of the configuration form.
   *
   * @see \Drupal\sapi\StatPluginMethodBase::methodForm()
   * @see \Drupal\sapi\StatPluginMethodBase::methodValidate()
   * @see \Drupal\sapi\StatPluginMethodBase::submit()
   */
  public function methodSubmit($form, &$form_state) {}

  /**
   * Sets a particular value in the statistics method settings.
   *
   * @param string $key
   *   The key of StatPluginMethodBase::$configuration to set.
   * @param mixed $value
   *   The value to set for the provided key.
   *
   * @see \Drupal\sapi\StatPluginMethodBase::$configuration
   */
  public function setConfig($key, $value) {
    $this->configuration[$key] = $value;
  }

  /**
   * Returns the configuration data for the statistics method plugin.
   *
   * @param string|NULL $key
   *   The key of the StatPluginMethodBase::$configuration to get or NULL if
   *   the whole configuration array is desired.
   *
   * @return array|boolean
   *   The plugin configuration array from StatPluginMethodBase::$configuration
   *   or the specific value desired. If the configuration doesn't exist, FALSE
   *   is returned.
   *
   * @see \Drupal\sapi\StatPluginMethodBase::$configuration
   */
  public function getConfig($key = NULL) {
    if ($key === NULL) {
      return $this->configuration;
    }
    else {
      return isset($this->configuration[$key]) ? $this->configuration[$key] : FALSE;
    }
  }

  /**
   * Returns the settings specified by a particular module.
   *
   * @param string $module
   *   The short name of the module whose settings we are setting.
   * @param string $key
   *   The key of StatPluginMethodBase::$configuration to set.
   * @param mixed $value
   *   The value to set for the provided key.
   */
  public function setModuleConfig($module, $key, $value) {
    $this->configuration['settings'][$module][$key] = $value;
  }

  /**
   * Returns the settings specified by a particular module.
   *
   * @param string|NULL $module
   *   The short name of the module whose settings we are retrieving.
   * @param string $key
   *   The key of StatPluginMethodBase::$configuration to get.
   *
   * @return array|boolean
   *   If neither $module nor $key are provided, an array of all module-specific
   *   settings. If only a $module is specified, only that module's settings are
   *   returned. If both a $module and $key are specified, then the specific
   *   key specified is returned; if there is nothing to return, an empty value
   *   of the appropriate type is returned.
   */
  public function getModuleConfig($module = NULL, $key = NULL) {
    $config = $this->getConfig('settings');
    if ($module === NULL) {
      return !empty($config) ? $config : array();
    }
    else {
      if ($key === NULL) {
        return isset($config[$module]) ? $config[$module] : array();
      }
      else {
        return isset($config[$module][$key]) ? $config[$module][$key] : FALSE;
      }
    }
  }

  /**
   * Translates a string to the current language or to a given language.
   * @see t()
   */
  protected function t($string, array $args = array(), array $options = array()) {
    return $this->stringTranslation->translate($string, $args, $options);
  }

}
