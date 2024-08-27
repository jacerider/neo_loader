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
   */
  public function getCssFile();

  /**
   * Returns human readable label.
   */
  public function getLabel();

}
