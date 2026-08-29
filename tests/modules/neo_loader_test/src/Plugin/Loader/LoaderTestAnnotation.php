<?php

declare(strict_types=1);

namespace Drupal\neo_loader_test\Plugin\Loader;

use Drupal\neo_loader\Plugin\LoaderPluginBase;

/**
 * A loader fixture declared with the legacy annotation.
 *
 * Kept deliberately, and not converted with the rest: it is the only proof
 * that a site's own `@Loader` class still resolves once the manager reads
 * attributes first.
 *
 * @Loader(
 *   id = "neo_loader_test_annotation",
 *   label = @Translation("Test annotation")
 * )
 */
final class LoaderTestAnnotation extends LoaderPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function setMarkup() {
    return '<div class="neo-loader-test-annotation"></div>';
  }

  /**
   * {@inheritdoc}
   */
  protected function setCssFile() {
    return $this->path . '/css/loader/test-annotation.css';
  }

}
