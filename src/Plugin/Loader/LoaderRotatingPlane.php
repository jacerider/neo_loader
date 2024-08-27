<?php

namespace Drupal\neo_loader\Plugin\Loader;

use Drupal\neo_loader\Plugin\LoaderPluginBase;

/**
 * Provides the LoaderRotatingPlane.
 *
 * @Loader(
 *   id = "rotating_plane",
 *   label = @Translation("Rotating plane")
 * )
 */
class LoaderRotatingPlane extends LoaderPluginBase {

  /**
   * Function to set markup.
   *
   * @inheritdoc
   */
  protected function setMarkup() {
    return '<div class="neo-loader-style neo-loader-rotating-plane"></div>';
  }

  /**
   * Function to set css file.
   *
   * @inheritdoc
   */
  protected function setCssFile() {
    return $this->path . '/css/loader/rotating-plane.scss';
  }

}
