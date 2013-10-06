<?php

/**
 * @file
 * Hooks provided by the Statistics API module.
 */


/**
 * @defgroup sapi_hooks Statistics API Hooks
 * @{
 * Functions to define and modify Statistics methods and structures and other
 * associated functionality.
 * @todo Add additional detail here.
 * @}
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Modify Stat Method definitions.
 *
 * @param array $info
 *   An associative array of Stat Method definitions, keyed by Stat Method IDs.
 *
 * @see \Drupal\sapi\Annotation\StatMethod
 * @see \Drupal\sapi\Plugin\StatPluginManager
 */
function hook_sapi_method_info_alter(&$info) {
  // Use a different instance class for my_method.
  $info['my_method']['class'] = '\Foo\bar\MyMethodClass';
}

/**
 * Modify Stat Data definitions.
 *
 * @param array $info
 *   An associative array of Stat Data definitions, keyed by Stat Data ID.
 *
 * @see \Drupal\sapi\Annotation\StatData
 * @see \Drupal\sapi\Plugin\StatPluginManager
 */
function hook_sapi_data_info_alter(&$info) {
  // Modify the default Stat Data description.
  $info['my_data']['description'] = t('My new description.');
}

/**
 * @} End of "addtogroup hooks".
 */
