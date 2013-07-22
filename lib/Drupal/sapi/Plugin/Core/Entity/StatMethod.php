<?php

/**
 * @file
 * Contains \Drupal\sapi\Plugin\Core\Entity\StatMethod.
 */

namespace Drupal\sapi\Plugin\Core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\sapi\StatMethodInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityStorageException;


/**
 * Defines the statistic method configuration entity.
 *
 * @EntityType(
 *   id = "stat_method",
 *   label = @Translation("Statistic method"),
 *   module = "sapi",
 *   controllers = {
 *     "storage" = "Drupal\sapi\StatMethodStorageController",
 *     "access" = "Drupal\sapi\StatMethodAccessController",
 *     "list" = "Drupal\sapi\StatMethodListController",
 *     "form" = {
 *       "edit" = "Drupal\sapi\StatMethodFormController",
 *       "toggle_status" = "Drupal\sapi\Form\StatMethodToggleStatus"
 *     },
 *   },
 *   config_prefix = "stat.method",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   }
 * )
 */
class StatMethod extends ConfigEntityBase implements StatMethodInterface {

  /**
   * The machine name of this statistics method.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the statistics method.
   *
   * @var string
   */
  public $label;

  /**
   * A brief description of this statistics method.
   *
   * @var string
   */
  public $description;

  /**
   * Help information shown to the user when configuring this stat method.
   *
   * @var string
   */
  public $help;

  /**
   * Module-specific settings for this stat method, keyed by module name.
   *
   * @var array
   */
  public $settings = array();

  /**
   * The statistics method plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * Overrides \Drupal\Core\Config\Entity\ConfigEntityBase::__construct().
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);

    $this->pluginManager = \Drupal::service('plugin.manager.sapi.method');
  }

  /**
   * {@inheritdoc}
   */
  public function uri() {
    return array(
      'path' => 'admin/config/statistics/methods/' . $this->id(),
      'options' => array(
        'entity_type' => $this->entityType,
        'entity' => $this,
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getModuleSettings($module) {
    if (isset($this->settings[$module]) && is_array($this->settings[$module])) {
      return $this->settings[$module];
    }
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    // Save the entity as usual.
    $return = parent::save();

    // Changes to stat method entities require a cache clear of related plugins.
    $this->pluginManager->clearCachedDefinitions();

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function enable() {
    try {
      $this->set('status', 1);
      $this->save();
      return SAVED_UPDATED;
    }
    catch (EntityStorageException $e) {
      watchdog('sapi', 'There was a problem enabling the %method statistics method: !message', array(
        '%method' => $this->id(),
        '!message' => $e->getMessage(),
      ), WATCHDOG_ERROR);
      return 0;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function disable() {
    try {
      $this->set('status', 0);
      $this->save();
      return SAVED_UPDATED;
    }
    catch (EntityStorageException $e) {
      watchdog('sapi', 'There was a problem disabling the %method statistics method: !message', array(
        '%method' => $this->id(),
        '!message' => $e->getMessage(),
      ), WATCHDOG_ERROR);
      return 0;
    }
  }

}
