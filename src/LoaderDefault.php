<?php

declare(strict_types=1);

namespace Drupal\neo_loader;

use Drupal\neo_loader\Plugin\LoaderPluginBase;

/**
 * The class every loader declaration is given.
 *
 * A loader whose whole content is a fixed block of markup does not need a
 * class of its own: it needs the markup, and the markup is in the
 * declaration. This is the class the manager hands such a definition, and its
 * markup is the definition's own.
 *
 * A declaration may name a `class` of its own instead, and a loader that
 * genuinely computes something still writes one — that is what the
 * `#[Loader]` attribute is for. This class is only the answer for the case
 * where there is nothing to compute.
 *
 * @see \Drupal\neo_loader\LoaderManager
 */
class LoaderDefault extends LoaderPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function setMarkup() {
    return $this->pluginDefinition['markup'] ?? '';
  }

  /**
   * {@inheritdoc}
   *
   * Passed straight through from the declaration, and empty when it declared
   * none. Deriving the stylesheet from the id belongs to the manager rather
   * than to a plugin, so this stays a pass-through until the manager owns the
   * question.
   */
  protected function setCssFile() {
    return $this->pluginDefinition['css'] ?? '';
  }

}
