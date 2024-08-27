<?php

namespace Drupal\neo_loader\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Loader item annotation object.
 *
 * @Annotation
 */
class Loader extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
