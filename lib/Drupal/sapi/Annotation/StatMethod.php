<?php

/**
 * @file
 * Contains \Drupal\sapi\Annotation\StatMethod.
 */

namespace Drupal\sapi\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Stat method annotation object.
 *
 * @see hook_sapi_method_info_alter()
 *
 * @Annotation
 */
class StatMethod extends Plugin {

  /**
   * The plugin ID.
   *
   * This will be used as the name of the stat method and should therefore be
   * globally unique.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the Stat method.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * A brief description of the Stat method.
   *
   * This will be shown when configuring this Stat method.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation (optional)
   */
  public $description = '';

  /**
   * Help text to be used with the Stat method.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation (optional)
   */
  public $help = '';

  /**
   * Indicates whether or not the Stat method is enabled.
   *
   * @var boolean
   */
  public $status;

  /**
   * An array of settings for this Stat method.
   *
   * @var array (optional)
   */
  public $settings = array();

}
