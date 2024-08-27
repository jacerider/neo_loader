<?php

namespace Drupal\neo_loader\Plugin\Loader;

use Drupal\neo_loader\Plugin\LoaderPluginBase;

/**
 * Provides the LoaderCubeGrid.
 *
 * @Loader(
 *   id = "cube_grid",
 *   label = @Translation("Cube gird")
 * )
 */
class LoaderCubeGrid extends LoaderPluginBase {

  /**
   * Function to set markup.
   *
   * @inheritdoc
   */
  protected function setMarkup() {
    return '<div class="neo-loader-style neo-loader-cube-grid">
              <div class="neo-loader-cube neo-loader-cube1"></div>
              <div class="neo-loader-cube neo-loader-cube2"></div>
              <div class="neo-loader-cube neo-loader-cube3"></div>
              <div class="neo-loader-cube neo-loader-cube4"></div>
              <div class="neo-loader-cube neo-loader-cube5"></div>
              <div class="neo-loader-cube neo-loader-cube6"></div>
              <div class="neo-loader-cube neo-loader-cube7"></div>
              <div class="neo-loader-cube neo-loader-cube8"></div>
              <div class="neo-loader-cube neo-loader-cube9"></div>
            </div>';
  }

  /**
   * Function to set css file.
   *
   * @inheritdoc
   */
  protected function setCssFile() {
    return $this->path . '/css/loader/cube-grid.scss';
  }

}
