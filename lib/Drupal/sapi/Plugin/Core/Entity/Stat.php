<?php

/**
 * @file
 * Contains Drupal\sapi\Plugin\Core\Entity\Stat.
 */


namespace Drupal\sapi\Plugin\Core\Entity;

use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Entity\DatabaseStorageController;
use Drupal\Core\Entity\EntityNG;
use Drupal\sapi\StatInterface;
use Drupal\Core\Annotation\Translation;


/**
 * Defines the statistic entity.
 *
 * @EntityType(
 *   id = "stat",
 *   label = @Translation("Statistic"),
 *   bundle_label = @Translation("Method"),
 *   module = "sapi",
 *   fieldable = FALSE,
 *   translatable = FALSE,
 *   permission_granularity = "bundle",
 *   base_table = "stat",
 *   controllers = {
 *     "storage" = "Drupal\sapi\StatStorageController",
 *     "render" = "Drupal\sapi\StatRenderController"
 *   },
 *   route_base_path = "admin/config/statistics/methods/{bundle}",
 *   menu_view_path = "stat/{bundle}/{stat}",
 *   links = {
 *     "canonical" = "/stat/{bundle}/{stat}"
 *   },
 *   entity_keys = {
 *     "id" = "sid",
 *     "bundle" = "method"
 *   },
 *   bundle_keys = {
 *     "bundle" = "id"
 *   }
 * )
 */
class Stat extends EntityNG implements StatInterface {

  /**
   * Implements Drupal\Core\Entity\EntityInterface::id().
   */
  public function id() {
    return $this->get('sid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageControllerInterface $storage_controller) {
    $this->set('created', REQUEST_TIME);
  }

}
