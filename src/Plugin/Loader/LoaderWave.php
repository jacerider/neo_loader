<?php

namespace Drupal\neo_loader\Plugin\Loader;

use Drupal\neo_loader\Plugin\LoaderPluginBase;

/**
 * Provides the LoaderWave.
 *
 * @Loader(
 *   id = "wave",
 *   label = @Translation("Wave")
 * )
 */
class LoaderWave extends LoaderPluginBase {

  /**
   * Function to set markup.
   *
   * @inheritdoc
   */
  protected function setMarkup() {
    return '<div class="neo-loader-style neo-loader-wave">
              <div class="neo-loader-rect neo-loader-rect1"></div>
              <div class="neo-loader-rect neo-loader-rect2"></div>
              <div class="neo-loader-rect neo-loader-rect3"></div>
              <div class="neo-loader-rect neo-loader-rect4"></div>
              <div class="neo-loader-rect neo-loader-rect5"></div>
            </div>';
  }

  /**
   * Function to set css file.
   *
   * @inheritdoc
   */
  protected function setCssFile() {
    return $this->path . '/css/loader/wave.scss';
  }

}
