<?php

namespace Drupal\neo_loader\Plugin\Loader;

use Drupal\neo_loader\Plugin\LoaderPluginBase;

/**
 * Provides the LoaderThreeBounce.
 *
 * @Loader(
 *   id = "three_bounce",
 *   label = @Translation("Three bounce")
 * )
 */
class LoaderThreeBounce extends LoaderPluginBase {

  /**
   * Function to set markup.
   *
   * @inheritdoc
   */
  protected function setMarkup() {
    return '<div class="neo-loader-style neo-loader-three-bounce">
              <div class="neo-loader-child neo-loader-bounce1"></div>
              <div class="neo-loader-child neo-loader-bounce2"></div>
              <div class="neo-loader-child neo-loader-bounce3"></div>
            </div>';
  }

  /**
   * Function to set css file.
   *
   * @inheritdoc
   */
  protected function setCssFile() {
    return $this->path . '/css/loader/three-bounce.scss';
  }

}
