<?php

/**
 * @file
 * Contains Drupal\sapi\Plugin\Core\Entity\Stat.
 */


namespace Drupal\sapi\Entity;

use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\TypedData\FALSE;
use Drupal\sapi\StatInterface;


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
 *     "render" = "Drupal\Core\Entity\EntityRenderController"
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
class Stat extends ContentEntityBase implements \IteratorAggregate, StatInterface {

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

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions($entity_type) {
    $properties['sid'] = array(
      'label' => t('Stat ID'),
      'description' => t('The stat ID.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $properties['method'] = array(
      'label' => t('Method'),
      'description' => t('The stat method.'),
      'type' => 'string_field',
      'read-only' => TRUE,
    );
    $properties['created'] = array(
      'label' => t('Created'),
      'description' => t('The time that the stat was created.'),
      'type' => 'integer_field',
    );

    return $properties;
  }
}
