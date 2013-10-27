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
   * The string translation service.
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

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
      'label' => $this->t('Stat ID'),
      'description' => $this->t('The stat ID.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $properties['method'] = array(
      'label' => $this->t('Method'),
      'description' => $this->t('The stat method.'),
      'type' => 'string_field',
      'read-only' => TRUE,
    );
    $properties['created'] = array(
      'label' => $this->t('Created'),
      'description' => $this->t('The time that the stat was created.'),
      'type' => 'integer_field',
    );

    // @todo This desperately needs to be injected, but can't be until this
    // issue is resolved: https://drupal.org/node/2015535
    $manager = \Drupal::service('plugin.manager.sapi.data');

    // Pull all properties from the Stat data plugin manager.
    foreach ($manager->getDefinitions() as $id => $definition) {
      $properties[$id] = $definition['typed_data'];

      // Disallow multi-value fields.
      $properties[$id]['list'] = FALSE;

      // Provide default values for convenience.
      if (!isset($properties[$id]['label'])) {
        $properties[$id]['label'] = $definition['label'];
      }
      if (!isset($properties[$id]['description'])) {
        $properties[$id]['description'] = $definition['description'];
      }
      if (!isset($properties[$id]['type'])) {
        $properties[$id]['type'] = self::mapTypedDataFromSchema($definition['schema']['type']);
      }
    }

    return $properties;
  }

  /**
   * Returns a TypedData type based on a schema type.
   *
   * @param $schema_type
   *   The type as defined in the schema.
   *
   * @return string
   *   The corresponding TypedData field type.
   */
  protected static function mapTypedDataFromSchema($schema_type) {
    $map = array(
      'int' => 'integer_field',
      'serial' => 'integer_field',
      'float' => 'float_field',
      'numeric' => 'float_field',
      'char' => 'string_field',
      'varchar' => 'string_field',
      'text' => 'string_field',
      'blob' => 'binary_field',
    );

    return isset($map[$schema_type]) ? $map[$schema_type] : 'string_field';
  }

  public function postCreate(EntityStorageControllerInterface $storage_controller) {
    parent::postCreate($storage_controller);

    // @todo This desperately needs to be injected, but can't be until this
    // issue is resolved: https://drupal.org/node/2015535
    $manager = \Drupal::service('plugin.manager.sapi.data');

    // Loop through all defined data plugins; execute and set their values.
    foreach ($manager->getDefinitions() as $id => $definition) {
      // Only set the value if it hasn't already been set.
      $value = $this->$id->value;
      if (empty($value)) {
        $instance = $manager->createInstance($id);
        $this->set($id, $instance->execute());
      }
    }
  }

  /**
   * Translates a string to the current language or to a given language.
   * @see t()
   */
  protected function t($string, array $args = array(), array $options = array()) {
    // @todo This desperately needs to be injected, but can't be until this
    // issue is resolved: https://drupal.org/node/2015535
    if (empty($this->stringTranslation)) {
      $this->stringTranslation = \Drupal::translation();
    }
    return $this->stringTranslation->translate($string, $args, $options);
  }

}
