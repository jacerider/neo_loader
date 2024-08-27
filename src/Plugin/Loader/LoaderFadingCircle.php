<?php

namespace Drupal\neo_loader\Plugin\Loader;

use Drupal\neo_loader\Plugin\LoaderPluginBase;

/**
 * Provides the LoaderFadingCircle.
 *
 * @Loader(
 *   id = "fading_circle",
 *   label = @Translation("Fading circle")
 * )
 */
class LoaderFadingCircle extends LoaderPluginBase {

  /**
   * Function set markup.
   *
   * @inheritdoc
   */
  protected function setMarkup() {
    return '<div class="neo-loader-style neo-loader-fading-circle">
              <div class="neo-loader-circle1 neo-loader-circle"></div>
              <div class="neo-loader-circle2 neo-loader-circle"></div>
              <div class="neo-loader-circle3 neo-loader-circle"></div>
              <div class="neo-loader-circle4 neo-loader-circle"></div>
              <div class="neo-loader-circle5 neo-loader-circle"></div>
              <div class="neo-loader-circle6 neo-loader-circle"></div>
              <div class="neo-loader-circle7 neo-loader-circle"></div>
              <div class="neo-loader-circle8 neo-loader-circle"></div>
              <div class="neo-loader-circle9 neo-loader-circle"></div>
              <div class="neo-loader-circle10 neo-loader-circle"></div>
              <div class="neo-loader-circle11 neo-loader-circle"></div>
              <div class="neo-loader-circle12 neo-loader-circle"></div>
            </div>';
  }

  /**
   * Function to set css file.
   *
   * @inheritdoc
   */
  protected function setCssFile() {
    return $this->path . '/css/loader/fading-circle.scss';
  }

}
