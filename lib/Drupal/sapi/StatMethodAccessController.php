<?php

/**
 * @file
 * Contains \Drupal\sapi\StatMethodAccessController.
 */

namespace Drupal\sapi;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access controller for the stat method entity.
 *
 * @see \Drupal\sapi\Plugin\Core\Entity\StatMethod.
 */
class StatMethodAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    if ($operation == 'delete') {
      return FALSE;
    }
    return $account->hasPermission('administer statistics methods');
  }

}
