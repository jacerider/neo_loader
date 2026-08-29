<?php

namespace Drupal\neo_loader;

use Drupal\Component\Plugin\Mapper\MapperInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\neo_loader\Annotation\Loader as LoaderAnnotation;
use Drupal\neo_loader\Attribute\Loader as LoaderAttribute;
use Drupal\neo_loader\Plugin\LoaderPluginInterface;

/**
 * Gathers the loader plugins.
 */
class LoaderManager extends DefaultPluginManager implements LoaderManagerInterface, MapperInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
  ) {
    // Both spellings are declared, so discovery reads attributes first and
    // annotations second and one definition set carries both. Declaring only
    // the annotation is the shape core deprecated in 11.2 and removes in 12.
    parent::__construct('Plugin/Loader', $namespaces, $module_handler, LoaderPluginInterface::class, LoaderAttribute::class, LoaderAnnotation::class);
    $this->alterInfo('neo_loader_info');
    $this->setCacheBackend($cache_backend, 'loader', ['loader']);
  }

  /**
   * {@inheritdoc}
   */
  public function getLoaderOptionList() {
    $options = [];
    foreach ($this->getDefinitions() as $definition) {
      $options[$definition['id']] = $definition['label'];
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstances() {
    $loaders = [];
    foreach ($this->getDefinitions() as $id => $definition) {
      $loaders[$id] = $this->createInstance($definition['id']);
    }
    return $loaders;
  }

  /**
   * Finds plugin definitions.
   *
   * @return array
   *   List of definitions to store in cache.
   */
  protected function findDefinitions() {
    $definitions = parent::findDefinitions();
    uasort($definitions, function ($a, $b) {
      return strnatcasecmp($a['label'], $b['label']);
    });
    return $definitions;
  }

}
