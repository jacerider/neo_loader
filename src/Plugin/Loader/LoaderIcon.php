<?php

namespace Drupal\neo_loader\Plugin\Loader;

use Drupal\neo_icon\IconTrait;
use Drupal\neo_loader\Plugin\LoaderPluginBase;

/**
 * Provides the LoaderIcon.
 *
 * @Loader(
 *   id = "icon",
 *   label = @Translation("Icon")
 * )
 */
class LoaderIcon extends LoaderPluginBase {

  use IconTrait;

  /**
   * {@inheritdoc}
   */
  public function setMarkup() {
    return $this->t('Loading...');
  }

  /**
   * {@inheritdoc}
   */
  public function getMarkup() {
    $markup = parent::getMarkup();
    $icon = $this->icon($markup, 'spinner');
    if ($icon) {
      $markup = $icon->iconOnly();
    }
    return '<div class="animate-spin">' . $markup . '</div>';
  }

  /**
   * Function to set css file.
   *
   * {@inheritdoc}
   */
  protected function setCssFile() {
    return $this->path . '/css/loader/icon.css';
  }

}
