<?php

namespace Drupal\neo_loader;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;

/**
 * Interface for the class that gathers the loader plugins.
 */
interface LoaderManagerInterface extends PluginManagerInterface, CachedDiscoveryInterface, CacheableDependencyInterface {

  /**
   * Get an options list suitable for form elements for loader selection.
   *
   * @return array
   *   An array of options keyed by plugin ID with label values.
   */
  public function getLoaderOptionList();

  /**
   * Loads all available loaders.
   *
   * @return \Drupal\neo_loader\Plugin\LoaderPluginInterface[]
   *   Return incative for All Loader Instances.
   */
  public function createInstances();

}
