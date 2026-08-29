<?php

namespace Drupal\neo_loader\Plugin\Loader;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\neo_icon\IconTrait;
use Drupal\neo_loader\Attribute\Loader;
use Drupal\neo_loader\Plugin\LoaderPluginBase;

/**
 * Provides the LoaderIcon.
 */
#[Loader(
  id: 'icon',
  label: new TranslatableMarkup('Icon'),
  // The one shipped loader that has to declare this. Every other definition
  // either is a declaration or agrees with the derived path, but a class
  // plugin declaring no `css` is instantiated to be asked — and instantiating
  // this one drags `neo_icon`'s icon trait into library discovery, which is
  // half of what moving the stylesheet onto the definition is for.
  css: 'src/css/loader/icon.css',
)]
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
