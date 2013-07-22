<?php

/**
 * @file
 * Contains \Drupal\sapi\StatPluginMethodInterface.
 */

namespace Drupal\sapi;


/**
 * Defines the required interface for all statistics method plugins.
 *
 * @todo Add detailed documentation here explaining SAPI's architecture and the
 *   relationships between the various classes and interfaces.
 *
 * @see \Drupal\sapi\StatPluginMethodBase
 */
interface StatPluginMethodInterface {

  /**
   * Returns the settings for this statistics method plugin.
   *
   * @return array
   *   An associative array of stat method settings for this method, keyed by the
   *   setting name.
   */
  public function settings();

  /**
   * Indicates whether or not the statistics method should be called.
   *
   * This method allows base implementations to add general restrictions that
   * should apply to all extending statistics method plugins.
   *
   * @return bool
   *   TRUE if the method should not be called, or FALSE otherwise.
   */
  public function restricted();

  /**
   * Called prior to this Statistics method's primary data collection.
   */
  public function preCollect();

  /**
   * The primary data collection method for this Statistics method.
   */
  public function collect();

  /**
   * Called following this Statistics method's primary data collection.
   */
  public function postCollect();

  /**
   * The primary execution handler for the this Statistics method. Note that
   * this must take access/restrictions into account.
   *
   * @see StatPluginMethodInterface::restricted()
   */
  public function execute();

  /**
   * The data management method for this Statistics method. This is called for
   * all installed methods once per cron run; it can be used to perform regular
   * calculations, rotate/archive data, or what have you.
   */
  public function manageData();

  /**
   * Constructs the statistics method configuration form.
   *
   * This allows base implementations to add a generic configuration form for
   * extending statistics method plugins.
   *
   * @param array $form
   *   The form definition array for the statistics method configuration form.
   * @param array $form_state
   *   An array containing the current state of the configuration form.
   * @param StatMethodInterface $method
   *   The StatMethod associated with this particular method plugin.
   *
   * @return array $form
   *   The renderable form array representing the entire configuration form.
   */
  public function form(array $form, array &$form_state);

  /**
   * Handles form validation for the statistics method configuration form.
   *
   * @param array $form
   *   The form definition array for the statistics method configuration form.
   * @param array $form_state
   *   An array containing the current state of the configuration form.
   */
  public function validate(array $form, array &$form_state);

  /**
   * Handles form submissions for the statisticsm ethod configuration form.
   *
   * @param array $form
   *   The form definition array for the statistics method configuration form.
   * @param array $form_state
   *   An array containing the current state of the configuration form.
   */
  public function submit(array $form, array &$form_state);

}
