<?php

namespace Drupal\neo_loader\Plugin\Loader;

use Drupal\neo_loader\Plugin\LoaderPluginBase;

/**
 * Provides the LoaderChasingDots.
 *
 * @Loader(
 *   id = "chasing_dots",
 *   label = @Translation("Chasing dots")
 * )
 */
class LoaderChasingDots extends LoaderPluginBase {

  /**
   * Function to set markup.
   *
   * @inheritdoc
   */
  protected function setMarkup() {
    return '<div class="neo-loader-style neo-loader-chasing-dots">
              <div class="neo-loader-child neo-loader-dot1"></div>
              <div class="neo-loader-child neo-loader-dot2"></div>
            </div>';
  }

  /**
   * Function to set css file.
   *
   * @inheritdoc
   */
  protected function setCssFile() {
    return $this->path . '/css/loader/chasing-dots.scss';
  }

}
