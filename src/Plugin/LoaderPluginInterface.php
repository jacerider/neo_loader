<?php

namespace Drupal\neo_loader\Plugin;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Interface LoaderInterface.
 */
interface LoaderPluginInterface extends PluginInspectionInterface, DerivativeInspectionInterface {

  /**
   * Returns markup for loader.
   */
  public function getMarkup();

  /**
   * Returns path to css file.
   *
   * @deprecated in neo_loader:1.1.0 and is removed from neo_loader:2.0.0. Use
   *   \Drupal\neo_loader\LoaderManagerInterface::getCssFile() instead.
   *
   * @phpcs:ignore Drupal.Commenting.Deprecated.DeprecatedWrongSeeUrlFormat
   * @see \Drupal\neo_loader\LoaderManagerInterface::getCssFile()
   */
  public function getCssFile();

  /**
   * Returns human readable label.
   */
  public function getLabel();

}
