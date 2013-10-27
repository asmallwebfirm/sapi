<?php

/**
 * @file
 * Contains \Drupal\sapi\Form\StatMethodToggleStatus.
 */

namespace Drupal\sapi\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for statistics method one-click enablement/disablement.
 */
class StatMethodToggleStatus extends EntityConfirmFormBase implements ContainerInjectionInterface {

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The route provider object.
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $router;

  /**
   * The toggle value for this particular request.
   */
  protected $toggle;

  /**
   * The string translation service.
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * Constructs a new NodeTypeDeleteConfirm object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Request $request, RouteProviderInterface $routeProvider, TranslationInterface $stringTranslation) {
    $this->request = $request;
    $this->routeProvider = $routeProvider;
    $this->stringTranslation = $stringTranslation;
    $path_parts = explode('/', $this->request->getPathInfo());
    $this->toggle = array_pop($path_parts);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request'),
      $container->get('router.route_provider'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    if ($this->toggle == 'disable') {
      return $this->t('Are you sure you want to disable the %method statistics method?', array('%method' => $this->entity->label()));
    }
    else {
      return $this->t('Are you sure you want to enable the %method statistics method?', array('%method' => $this->entity->label()));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return array('route_name' => 'sapi_stat_method_overview');
  }

  /**
   * Returns the path used as the "cancel" link for this controller.
   *
   * @return string
   *   The cancellation path for this controller.
   */
  public function getCancelPath() {
    $cancel_route = $this->getCancelRoute();
    $route_name = $cancel_route['route_name'];
    return $this->routeProvider->getRouteByName($route_name)->getPath();
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
      return $this->t('Disable');
    }
    else {
      return $this->t('Enable');
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
      drupal_set_title($this->t('There was a problem processing your request'), PASS_THROUGH);
      if ($status) {
        $caption = $this->t('You cannot enable the %method statistics method because it is already enabled. Did you mean to <a href="@url">disable it</a>?', array(
          '%method' => $this->entity->label(),
          '@url' => url($this->getDisablePath()),
        ));
      }
      else {
        $caption = $this->t('You cannot disable the %method statistics method because it is already disabled. Did you mean to <a href="@url">enable it</a>?', array(
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
        drupal_set_message($this->t('The %method statistics method was disabled successfully.', $t_args));
        watchdog('sapi', 'Disabled %method statistics method.', $t_args, WATCHDOG_NOTICE);
      }
      else {
        drupal_set_message($this->t('The %method statistics method was enabled successfully.', $t_args));
        watchdog('sapi', 'Enabled %method statistics method.', $t_args, WATCHDOG_NOTICE);
      }
    }

    $form_state['redirect'] = $this->getCancelPath();
  }

  /**
   * Translates a string to the current language or to a given language.
   * @see t()
   */
  protected function t($string, array $args = array(), array $options = array()) {
    return $this->stringTranslation->translate($string, $args, $options);
  }

}
