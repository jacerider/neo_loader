<?php

namespace Drupal\neo_loader\Plugin\Loader;

use Drupal\neo_loader\Plugin\LoaderPluginBase;

/**
 * Provides the LoaderPulse.
 *
 * @Loader(
 *   id = "pulse",
 *   label = @Translation("Pulse")
 * )
 */
class LoaderPulse extends LoaderPluginBase {

  /**
   * Function to set markup.
   *
   * @inheritdoc
   */
  protected function setMarkup() {
    return '<div class="neo-loader-style neo-loader-spinner neo-loader-spinner-pulse"></div>';
  }

  /**
   * Function to set css file.
   *
   * @inheritdoc
   */
  protected function setCssFile() {
    return $this->path . '/css/loader/pulse.scss';
  }

}
