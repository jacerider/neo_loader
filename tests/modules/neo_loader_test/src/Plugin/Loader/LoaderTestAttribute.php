<?php

declare(strict_types=1);

namespace Drupal\neo_loader_test\Plugin\Loader;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\neo_loader\Attribute\Loader;
use Drupal\neo_loader\Plugin\LoaderPluginBase;

/**
 * A loader fixture declared with the attribute.
 *
 * This is the spelling the module is moving to, and it is declared by an
 * extension other than `neo_loader` on purpose: a fixture inside the module
 * itself would prove that the discovery reads its own directory, not that it
 * reads a site's.
 */
#[Loader(
  id: 'neo_loader_test_attribute',
  label: new TranslatableMarkup('Test attribute'),
)]
final class LoaderTestAttribute extends LoaderPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function setMarkup() {
    return '<div class="neo-loader-test-attribute"></div>';
  }

  /**
   * {@inheritdoc}
   */
  protected function setCssFile() {
    return $this->path . '/css/loader/test-attribute.css';
  }

}
