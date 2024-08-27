<?php

namespace Drupal\neo_loader\Element;

use Drupal\Core\Render\Attribute\RenderElement;
use Drupal\Core\Render\Element\RenderElementBase;

/**
 * Provides a render element for eXo loaders.
 */
#[RenderElement('neo_loader')]
class Loader extends RenderElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#theme' => 'neo_loader',
      '#loader' => 'chasing_dots',
      '#title' => '',
    ];
  }

}
