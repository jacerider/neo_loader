<?php

declare(strict_types=1);

namespace Drupal\neo_loader_test\Plugin\Loader;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\neo_loader\Attribute\Loader;
use Drupal\neo_loader\Plugin\LoaderPluginBase;

/**
 * A class plugin claiming an id the fixture module also declares in YAML.
 *
 * Its whole purpose is to lose. Both halves of the collision are spelled with
 * markup of their own so that a test can say which one answered rather than
 * only that something did.
 *
 * @see \Drupal\neo_loader_test\neo_loader_test.neo.loader.yml
 */
#[Loader(
  id: 'neo_loader_test_collision',
  label: new TranslatableMarkup('Test collision class'),
)]
final class LoaderTestCollision extends LoaderPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function setMarkup() {
    return '<div class="neo-loader-test-collision-class"></div>';
  }

  /**
   * {@inheritdoc}
   */
  protected function setCssFile() {
    return $this->path . '/css/loader/test-collision.css';
  }

}
