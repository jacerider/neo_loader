<?php

declare(strict_types=1);

namespace Drupal\Tests\neo_loader\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\neo_loader\LoaderManagerInterface;

/**
 * Base class for the loader characterisation kernel tests.
 *
 * Every seam this suite pins needs a real container: discovery needs the real
 * extension list, library building needs the real library registry, and the
 * theme hook needs the real settings repository and render pipeline. A unit
 * test of any of the three would assert mocks, so the whole suite is kernel.
 *
 * The module list is the smallest one that boots the three seams.
 * `neo_settings` is here because `neo_loader.settings` inherits from
 * `neo_settings.repository`, and `template_preprocess_neo_loader()` reads the
 * active settings on every call. `neo_loader`'s own config is installed
 * because the same function falls back to the stored `loader` and `color`.
 */
abstract class LoaderKernelTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'neo_settings',
    'neo_loader',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['neo_loader']);
  }

  /**
   * The loader manager, as a site gets it.
   *
   * @return \Drupal\neo_loader\LoaderManagerInterface
   *   The manager under test.
   */
  protected function loaderManager(): LoaderManagerInterface {
    $manager = $this->container->get('plugin.manager.neo_loader');
    assert($manager instanceof LoaderManagerInterface);
    return $manager;
  }

}
