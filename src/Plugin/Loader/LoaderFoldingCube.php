<?php

namespace Drupal\neo_loader\Plugin\Loader;

use Drupal\neo_loader\Plugin\LoaderPluginBase;

/**
 * Provides the LoaderFoldingCube.
 *
 * @Loader(
 *   id = "folding_cube",
 *   label = @Translation("Folding cube")
 * )
 */
class LoaderFoldingCube extends LoaderPluginBase {

  /**
   * Function to set markup.
   *
   * @inheritdoc
   */
  protected function setMarkup() {
    return '<div class="neo-loader-style neo-loader-folding-cube">
              <div class="neo-loader-cube1 neo-loader-cube"></div>
              <div class="neo-loader-cube2 neo-loader-cube"></div>
              <div class="neo-loader-cube4 neo-loader-cube"></div>
              <div class="neo-loader-cube3 neo-loader-cube"></div>
            </div>';
  }

  /**
   * Function to set css file.
   *
   * @inheritdoc
   */
  protected function setCssFile() {
    return $this->path . '/css/loader/folding-cube.scss';
  }

}
