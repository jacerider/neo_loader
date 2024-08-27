<?php

namespace Drupal\neo_loader\Plugin\Loader;

use Drupal\neo_loader\Plugin\LoaderPluginBase;

/**
 * Provides the LoaderCircle.
 *
 * @Loader(
 *   id = "circle",
 *   label = @Translation("Circle")
 * )
 */
class LoaderCircle extends LoaderPluginBase {

  /**
   * Function to set markup.
   *
   * @inheritdoc
   */
  protected function setMarkup() {
    return '<div class="neo-loader-style neo-loader-circle">
              <div class="neo-loader-circle1 neo-loader-child"></div>
              <div class="neo-loader-circle2 neo-loader-child"></div>
              <div class="neo-loader-circle3 neo-loader-child"></div>
              <div class="neo-loader-circle4 neo-loader-child"></div>
              <div class="neo-loader-circle5 neo-loader-child"></div>
              <div class="neo-loader-circle6 neo-loader-child"></div>
              <div class="neo-loader-circle7 neo-loader-child"></div>
              <div class="neo-loader-circle8 neo-loader-child"></div>
              <div class="neo-loader-circle9 neo-loader-child"></div>
              <div class="neo-loader-circle10 neo-loader-child"></div>
              <div class="neo-loader-circle11 neo-loader-child"></div>
              <div class="neo-loader-circle12 neo-loader-child"></div>
            </div>';
  }

  /**
   * Function set css file.
   *
   * @inheritdoc
   */
  protected function setCssFile() {
    return $this->path . '/css/loader/circle.scss';
  }

}
