<?php

/**
 * @file
 * Contains \Drupal\sapi\Annotation\StatData.
 */

namespace Drupal\sapi\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Stat data annotation object.
 *
 * @see hook_sapi_data_info_alter()
 *
 * @Annotation
 */
class StatData extends Plugin {

  /**
   * The plugin ID.
   *
   * This will be used as the column name on the {stat} table when the default
   * database storage controller is used and should therefore be globally
   * unique.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the Stat datum.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * A brief description of the Stat datum.
   *
   * This will be shown when configuring this Stat datum.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation (optional)
   */
  public $description = '';

  /**
   * Help text to be used with the Stat datum.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation (optional)
   */
  public $help = '';

  /**
   * The schema for the Stat datum.
   *
   * This should take the form of a Schema API field schema array.
   *
   * @var array
   */
  public $schema;

  /**
   * The typed data definition for this datum.
   *
   * Note that if no label or description is provided, the label and description
   * properties defined at the base level of the annotation will be used. If no
   * type is given, a best guess will be made based on the schema provided.
   *
   * @see \Drupal\Core\TypedData\TypedDataManager::create().
   * @see \Drupal\sapi\StatStorageController::baseFieldDefinitions().
   *
   * @var array (optional)
   */
  public $typed_data = array();

}
