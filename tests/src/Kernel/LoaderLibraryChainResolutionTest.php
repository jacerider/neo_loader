<?php

declare(strict_types=1);

namespace Drupal\Tests\neo_loader\Kernel;

use Drupal\Core\Asset\LibraryDependencyResolverInterface;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Pins that the module's inverted ajax dependency still resolves.
 *
 * The unit test beside this one asserts the rule the library-info alter
 * applies. What it cannot answer is whether the arrangement that rule produces
 * is one the real library registry can resolve at all, because the arrangement
 * is a loop with one link missing:
 *
 * - `neo_loader/ajax` depends on `core/drupal.ajax`;
 * - `core/drupal.ajax` is made to depend on `neo_loader/loader-ajax`, the
 *   **ajax override library**, by the module's own alter;
 * - `neo_loader/loader-ajax` depends back on `neo_loader/loader`.
 *
 * The link that is deliberately absent is the obvious one: the override does
 * not declare `core/drupal.ajax`, even though it patches `Drupal.Ajax`, and
 * `neo_loader.libraries.yml` explains at length that declaring it would close
 * the loop. Core's dependency resolver recurses into a library's dependencies
 * before recording the library itself, so a closed loop does not resolve to a
 * set — it recurses until the process dies. This test is the machine-checkable
 * form of that prose comment, and it is the property any future rework of the
 * inversion has to keep green whatever else it changes.
 *
 * @see neo_loader_library_info_alter()
 * @see \Drupal\Tests\neo_loader\Unit\LoaderLibraryDependencyAlterTest
 */
#[Group('neo_loader')]
final class LoaderLibraryChainResolutionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   *
   * `neo_settings` is here because `neo_loader.settings` inherits from its
   * repository, and the alter reads the active loader off it. `neo_icon` is
   * `neo_loader`'s own, for the icon loader plugin's class: core's attribute
   * discovery drops a plugin whose class needs an uninstalled provider, and a
   * kernel test enables exactly what it lists rather than resolving the chain
   * a site installs through.
   */
  protected static $modules = [
    'system',
    'neo_settings',
    'neo_icon',
    'neo_loader',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // The alter's second branch reads the active loader out of stored
    // settings, so the shipped default is what decides which built loader
    // library joins the chain below.
    $this->installConfig(['neo_loader']);
  }

  /**
   * Tests that the loader ajax chain resolves to a terminating set.
   */
  public function testResolvesTheLoaderAjaxChainToTheTerminatingSetContainingTheOverrideAndTheLoaderLibrary(): void {
    // First, the link whose absence is what makes the rest of this test able
    // to finish. Asserted before anything is resolved on purpose: if this ever
    // becomes false, the resolution below is the thing that never returns, and
    // a failing assertion is a far better way to find that out than a test run
    // that does not end.
    $override = $this->definition('loader-ajax');
    $this->assertNotContains(
      'core/drupal.ajax',
      $override['dependencies'] ?? [],
      'The ajax override library now declares core\'s ajax library, which closes the dependency loop the module\'s alter opens.',
    );

    $chain = $this->resolve('neo_loader/ajax');

    // The three libraries the loop is made of, all reached from a single
    // starting point that names only the first of them.
    $this->assertContains(
      'core/drupal.ajax',
      $chain,
      'Resolving the module\'s ajax library no longer reaches core\'s ajax library.',
    );
    $this->assertContains(
      'neo_loader/loader-ajax',
      $chain,
      'Resolving the module\'s ajax library no longer reaches the ajax override library.',
    );
    $this->assertContains(
      'neo_loader/loader',
      $chain,
      'Resolving the module\'s ajax library no longer reaches the module\'s loader library.',
    );

    // And the loader library carries the active loader's own library with it,
    // which is the second branch of the same alter arriving in the same set.
    $this->assertContains(
      'neo_loader/plugin.wave',
      $chain,
      'Resolving the module\'s ajax library no longer reaches the active loader\'s library.',
    );

    // A resolved set names every library exactly once. This is what
    // "terminates" looks like from the outside: the resolver walks the loop,
    // records each library once and stops, rather than revisiting one.
    $this->assertSame(
      array_values(array_unique($chain)),
      $chain,
      'The resolved library set names a library more than once.',
    );

    // The resolver emits dependencies before their dependents, so this is the
    // load order a page gets — and it is the answer to why the override chunk
    // runs before the object it patches exists. Nobody wrote that order down;
    // it falls out of the inversion.
    $this->assertLessThan(
      array_search('core/drupal.ajax', $chain, TRUE),
      array_search('neo_loader/loader-ajax', $chain, TRUE),
      'The ajax override library no longer loads before core\'s ajax library.',
    );
    $this->assertLessThan(
      array_search('neo_loader/loader-ajax', $chain, TRUE),
      array_search('neo_loader/loader', $chain, TRUE),
      'The module\'s loader library no longer loads before the ajax override library.',
    );
  }

  /**
   * Resolves a library and everything it depends on, as a page request does.
   *
   * @param string $library
   *   The fully qualified library name to start from.
   *
   * @return string[]
   *   The library names, in the order they would be loaded.
   */
  private function resolve(string $library): array {
    $resolver = $this->container->get('library.dependency_resolver');
    assert($resolver instanceof LibraryDependencyResolverInterface);
    return $resolver->getLibrariesWithDependencies([$library]);
  }

  /**
   * Reads one of the module's library definitions out of the real registry.
   *
   * @param string $name
   *   The library's name within the module.
   *
   * @return array
   *   The library definition, as every consumer of the registry sees it.
   */
  private function definition(string $name): array {
    $discovery = $this->container->get('library.discovery');
    assert($discovery instanceof LibraryDiscoveryInterface);
    $definition = $discovery->getLibraryByName('neo_loader', $name);
    $this->assertIsArray($definition, 'The module declares no library called ' . $name . '.');
    return $definition;
  }

}
