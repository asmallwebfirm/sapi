<?php

/**
 * @file
 * Contains \Drupal\sapi\Form\StatMethodToggleStatus.
 */

namespace Drupal\sapi\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Entity\EntityControllerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for statistics method one-click enablement/disablement.
 */
class StatMethodToggleStatus extends EntityConfirmFormBase implements EntityControllerInterface {

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The toggle value for this particular request.
   */
  protected $toggle;

  /**
   * Constructs a new NodeTypeDeleteConfirm object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(ModuleHandlerInterface $module_handler, Request $request) {
    parent::__construct($module_handler);
    $this->request = $request;
    $path_parts = explode('/', $this->request->getPathInfo());
    $this->toggle = array_pop($path_parts);
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, $entity_type, array $entity_info) {
    return new static(
      $container->get('module_handler'),
      $container->get('request')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    if ($this->toggle == 'disable') {
      return t('Are you sure you want to disable the %method statistics method?', array('%method' => $this->entity->label()));
    }
    else {
      return t('Are you sure you want to enable the %method statistics method?', array('%method' => $this->entity->label()));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelPath() {
    return 'admin/config/statistics/methods';
  }

  /**
   * Returns the path at which this statistics method can be enabled.
   */
  public function getEnablePath() {
    return $this->getCancelPath() . '/' . $this->entity->id() . '/enable';
  }

  /**
   * Returns the path at which this statistics method can be disabled.
   */
  public function getDisablePath() {
    return $this->getCancelPath() . '/' . $this->entity->id() . '/disable';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    if ($this->toggle == 'disable') {
      return t('Disable');
    }
    else {
      return t('Enable');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    // Define invalid states.
    $status = $this->entity->get('status');
    $states = array(
      'disable' => 0,
      'enable' => 1,
    );

    // Don't let the user enable that which is already enabled (or vice versa).
    if ($status == $states[$this->toggle]) {
      drupal_set_title(t('There was a problem processing your request'), PASS_THROUGH);
      if ($status) {
        $caption = t('You cannot enable the %method statistics method because it is already enabled. Did you mean to <a href="@url">disable it</a>?', array(
          '%method' => $this->entity->label(),
          '@url' => url($this->getDisablePath()),
        ));
      }
      else {
        $caption = t('You cannot disable the %method statistics method because it is already disabled. Did you mean to <a href="@url">enable it</a>?', array(
          '%method' => $this->entity->label(),
          '@url' => url($this->getEnablePath()),
        ));
      }

      $form['description'] = array('#markup' => $caption);
      return $form;
    }

    return parent::buildForm($form, $form_state, $this->request);
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $saved = $this->entity->{$this->toggle}();
    $t_args = array('%method' => $this->entity->label());
    if ($saved == SAVED_UPDATED) {
      if ($this->toggle == 'disable') {
        drupal_set_message(t('The %method statistics method was disabled successfully.', $t_args));
        watchdog('sapi', 'Disabled %method statistics method.', $t_args, WATCHDOG_NOTICE);
      }
      else {
        drupal_set_message(t('The %method statistics method was enabled successfully.', $t_args));
        watchdog('sapi', 'Enabled %method statistics method.', $t_args, WATCHDOG_NOTICE);
      }
    }

    $form_state['redirect'] = $this->getCancelPath();
  }

}
