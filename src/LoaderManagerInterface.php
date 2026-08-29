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

  /**
   * Gets a loader's stylesheet.
   *
   * The manager owns this question rather than the loader, so that a loader
   * can no longer disagree with the file sitting next to it on disk. The
   * definition's `css` when it declares one, and otherwise the path derived
   * from the id. A class-based loader that declares none is instantiated and
   * asked, which is what keeps an existing third-party loader's stylesheet
   * exactly where it was.
   *
   * @param string $id
   *   The loader id.
   *
   * @return string
   *   The stylesheet path, relative to the extension that declared the
   *   loader.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   When no loader is defined for the id.
   */
  public function getCssFile(string $id): string;

}
