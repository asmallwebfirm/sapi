<?php

/**
 * @file
 * Contains \Drupal\sapi\StatMethodInterface.
 */

namespace Drupal\sapi;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a stat method entity.
 */
interface StatMethodInterface extends ConfigEntityInterface {

  /**
   * Enables a statistics method.
   *
   * @return
   *   Either SAVED_UPDATED or 0, depending on whether the status was changed.
   */
  public function enable();

  /**
   * Disables a statistics method.
   *
   * @return
   *   Either SAVED_UPDATED or 0, depending on whether the status was changed.
   */
  public function disable();

}
