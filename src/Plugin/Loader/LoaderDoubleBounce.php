<?php

namespace Drupal\neo_loader\Plugin\Loader;

use Drupal\neo_loader\Plugin\LoaderPluginBase;

/**
 * Provides the LoaderDoubleBounce.
 *
 * @Loader(
 *   id = "double_bounce",
 *   label = @Translation("Double bounce")
 * )
 */
class LoaderDoubleBounce extends LoaderPluginBase {

  /**
   * Function to set markup.
   *
   * @inheritdoc
   */
  protected function setMarkup() {
    return '<div class="neo-loader-style neo-loader-double-bounce">
                <div class="neo-loader-child neo-loader-double-bounce1"></div>
                <div class="neo-loader-child neo-loader-double-bounce2"></div>
            </div>';
  }

  /**
   * Function to set css file.
   *
   * @inheritdoc
   */
  protected function setCssFile() {
    return $this->path . '/css/loader/double-bounce.scss';
  }

}
