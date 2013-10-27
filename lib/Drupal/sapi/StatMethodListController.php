<?php

/**
 * Contains \Drupal\sapi\StatMethodListController.
 */

namespace Drupal\sapi;

use Drupal\Core\Config\Entity\ConfigEntityListController;
use Drupal\Core\Entity\EntityControllerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Component\Utility\String;

/**
 * Provides a listing of statistic methods.
 */
class StatMethodListController extends ConfigEntityListController implements EntityControllerInterface {

  /**
   * The url generator service.
   *
   * @var \Drupal\Core\Routing\PathBasedGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The string translation service.
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * Constructs a StatMethodFormController object.
   *
   * @param string $entity_type
   *   The type of entity to be listed.
   * @param array $entity_info
   *   An array of entity info for the entity type.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $storage
   *   The entity storage controller class.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke hooks on.
   * @param \Drupal\Core\Routing\PathBasedGeneratorInterface $url_generator
   *   The url generator service.
   */
  public function __construct($entity_type, array $entity_info, EntityStorageControllerInterface $storage, ModuleHandlerInterface $module_handler, UrlGeneratorInterface $url_generator, TranslationInterface $stringTranslation) {
    parent::__construct($entity_type, $entity_info, $storage, $module_handler);
    $this->urlGenerator = $url_generator;
    $this->stringTranslation = $stringTranslation;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, $entity_type, array $entity_info) {
    return new static(
      $entity_type,
      $entity_info,
      $container->get('plugin.manager.entity')->getStorageController($entity_type),
      $container->get('module_handler'),
      $container->get('url_generator'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $row['title'] = $this->t('Name');
    $row['description'] = array(
      'data' => $this->t('Description'),
      'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
    );
    $row['operations'] = $this->t('Operations');
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['title'] = array(
      'data' => String::checkPlain($entity->label()),
      'class' => array('menu-label'),
    );
    $row['description'] = Xss::filterAdmin($entity->description);
    $row['operations']['data'] = $this->buildOperations($entity);
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    $uri = $entity->uri();

    // Provide a simple enable/disable toggle.
    if ($entity->access('update')) {
      $enabled = $entity->get('status');
      $operations['status_toggle'] = array(
        'title' => $enabled ? $this->t('Disable') : $this->t('Enable'),
        'href' => $uri['path'] . ($enabled ? '/disable' : '/enable'),
        'options' => $uri['options'],
        'weight' => $operations['edit']['weight'] + 1,
      );
    }

    // All stats methods are generated via plugins and cannot be deleted.
    unset($operations['delete']);

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['#empty'] = $this->t('No statistics methods available.');

    return $build;
  }

  /**
   * Translates a string to the current language or to a given language.
   * @see t()
   */
  protected function t($string, array $args = array(), array $options = array()) {
    return $this->stringTranslation->translate($string, $args, $options);
  }

}
