<?php

/**
 * @file
 * Contains \Drupal\sapi\StatDataPluginInterface.
 */

namespace Drupal\sapi;


/**
 * Defines the required interface for all statistics data plugins.
 *
 * @todo Add detailed documentation here explaining SAPI's architecture and the
 *   relationships between the various classes and interfaces.
 *
 * @see \Drupal\sapi\StatPluginDataBase
 */
interface StatDataPluginInterface {

  /**
   * Returns the settings for this statistics data plugin.
   *
   * @return array
   *   An associative array of stat data settings for this method, keyed by the
   *   setting name.
   */
  public function settings();

  /**
   * The primary execution handler for the this Statistics data. Returns data
   * suitable for storage with the associated Stat method.
   *
   * @return mixed
   */
  public function execute();

  /**
   * Constructs the statistics data configuration form.
   *
   * This allows base implementations to add a generic configuration form for
   * extending statistics data plugins.
   *
   * @param array $form
   *   The form definition array for the statistics data configuration form.
   * @param array $form_state
   *   An array containing the current state of the configuration form.
   * @param StatMethodInterface $method
   *   The StatMethod associated with this particular data plugin.
   *
   * @return array $form
   *   The renderable form array representing the entire configuration form.
   */
  public function form(array $form, array &$form_state);

  /**
   * Handles form validation for the statistics data configuration form.
   *
   * @param array $form
   *   The form definition array for the statistics data configuration form.
   * @param array $form_state
   *   An array containing the current state of the configuration form.
   */
  public function validate(array $form, array &$form_state);

  /**
   * Handles form submissions for the statistics data configuration form.
   *
   * @param array $form
   *   The form definition array for the statistics data configuration form.
   * @param array $form_state
   *   An array containing the current state of the configuration form.
   */
  public function submit(array $form, array &$form_state);

}
