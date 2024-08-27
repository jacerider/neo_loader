<?php

namespace Drupal\neo_loader\Plugin\Loader;

use Drupal\neo_loader\Plugin\LoaderPluginBase;

/**
 * Provides the LoaderWanderingCubes.
 *
 * @Loader(
 *   id = "wandering_cubes",
 *   label = @Translation("Wandering cubes")
 * )
 */
class LoaderWanderingCubes extends LoaderPluginBase {

  /**
   * Function to set markup.
   *
   * @inheritdoc
   */
  protected function setMarkup() {
    return '<div class="neo-loader-style neo-loader-wandering-cubes">
              <div class="neo-loader-cube neo-loader-cube1"></div>
              <div class="neo-loader-cube neo-loader-cube2"></div>
            </div>';
  }

  /**
   * Function to set css file.
   *
   * @inheritdoc
   */
  protected function setCssFile() {
    return $this->path . '/css/loader/wandering-cubes.scss';
  }

}
