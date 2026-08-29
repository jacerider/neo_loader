<?php

declare(strict_types=1);

namespace Drupal\Tests\neo_loader\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\neo_settings\Plugin\SettingsInterface;
use Drupal\neo_settings\SettingsRepositoryInterface;
use PHPUnit\Framework\Attributes\Group;

/**
 * Covers the two library dependencies the module injects into other people's.
 *
 * `neo_loader_library_info_alter()` does two unrelated things in one function,
 * each behind its own extension-name guard.
 *
 * The first inverts a dependency: core's own ajax library is made to depend on
 * the module's **ajax override library**, rather than the override declaring
 * the dependency the normal way round. That is why the override chunk loads
 * before the object it patches exists, and it is what the module's
 * `neo_loader.libraries.yml` spends eight prose lines explaining. It is
 * asserted here as today's answer and nothing more: the plan that reconsiders
 * the inversion owns changing this expectation, in the commit that changes the
 * behaviour.
 *
 * The second makes the module's own **loader library** depend on the library
 * built for the **active loader**, writing the loader id's underscores as
 * dashes on the way. Every assertion about that rule below uses a loader id
 * that actually has an underscore, because the shipped default — `wave` — does
 * not, so no site running the default has ever exercised the translation.
 *
 * The hook reaches exactly one service, the settings repository, and only down
 * the second branch. A container holding a double of it is therefore the whole
 * seam, and the two branches that must not reach it are asserted with a double
 * that fails if they do.
 *
 * @see neo_loader_library_info_alter()
 * @see \Drupal\Tests\neo_loader\Kernel\LoaderLibraryChainResolutionTest
 */
#[Group('neo_loader')]
final class LoaderLibraryDependencyAlterTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    require_once __DIR__ . '/../../../neo_loader.module';
  }

  /**
   * Tests that core's ajax library is made to depend on the override.
   */
  public function testAddsTheAjaxOverrideLibraryToTheCoreAjaxLibrary(): void {
    $this->expectNoSettingsLookup();

    $libraries = $this->alter([
      'drupal.ajax' => [
        'dependencies' => [
          'core/drupal',
          'core/drupalSettings',
          'core/once',
        ],
      ],
      'drupal.dialog' => ['dependencies' => ['core/jquery.ui.dialog']],
    ], 'core');

    // Appended, not assigned: core keeps every dependency it declared, and the
    // override goes on the end. The difference matters — assigning here would
    // strip core's ajax library of the things it is built out of.
    $this->assertSame(
      [
        'core/drupal',
        'core/drupalSettings',
        'core/once',
        'neo_loader/loader-ajax',
      ],
      $libraries['drupal.ajax']['dependencies'],
      'The hook no longer makes core\'s ajax library depend on the ajax override library.',
    );

    // Only that one library of core's is touched.
    $this->assertSame(
      ['core/jquery.ui.dialog'],
      $libraries['drupal.dialog']['dependencies'],
      'The hook altered a core library other than the ajax one.',
    );
  }

  /**
   * Tests that the dependency is created when core's ajax library has none.
   */
  public function testCreatesTheDependencyListWhenTheCoreAjaxLibraryDeclaresNone(): void {
    $this->expectNoSettingsLookup();

    $libraries = $this->alter(['drupal.ajax' => []], 'core');

    $this->assertSame(
      ['dependencies' => ['neo_loader/loader-ajax']],
      $libraries['drupal.ajax'],
      'The hook no longer creates the dependency list on a library that declares none.',
    );
  }

  /**
   * Tests that the active loader's library lands on the module's own.
   */
  public function testAddsTheActiveLoadersLoaderLibraryToTheModulesLoaderLibrary(): void {
    $this->setActiveLoader('wandering_cubes');

    $libraries = $this->alter([
      'loader' => [
        'dependencies' => [
          'core/drupal',
          'core/once',
          'core/drupalSettings',
        ],
      ],
      'loader-ajax' => ['dependencies' => ['neo_loader/loader', 'core/jquery']],
      'ajax' => ['dependencies' => ['core/drupal.ajax']],
      'autosubmit' => ['dependencies' => ['neo_loader/loader']],
    ], 'neo_loader');

    // Appended to what the YAML declares, in the same way core's is.
    $this->assertSame(
      [
        'core/drupal',
        'core/once',
        'core/drupalSettings',
        'neo_loader/plugin.wandering-cubes',
      ],
      $libraries['loader']['dependencies'],
      'The hook no longer makes the module\'s loader library depend on the active loader\'s library.',
    );

    // The module ships four libraries and exactly one of them gains anything.
    // In particular the ajax override library is left as declared: the reason
    // it does not name core's ajax library is that the first branch of this
    // same hook points that dependency the other way, and a library naming it
    // back is the cycle the kernel test beside this one exists to watch.
    $this->assertSame(
      ['neo_loader/loader', 'core/jquery'],
      $libraries['loader-ajax']['dependencies'],
      'The hook altered the ajax override library\'s dependencies.',
    );
    $this->assertSame(
      ['core/drupal.ajax'],
      $libraries['ajax']['dependencies'],
      'The hook altered the module\'s other ajax library\'s dependencies.',
    );
    $this->assertSame(
      ['neo_loader/loader'],
      $libraries['autosubmit']['dependencies'],
      'The hook altered the autosubmit library\'s dependencies.',
    );
  }

  /**
   * Tests that the loader id is written with dashes for its underscores.
   */
  public function testWritesTheLoaderIdWithDashesWhereTheIdHasUnderscores(): void {
    // Eight of the twelve shipped loader ids carry an underscore and the
    // default carries none, so this rule runs on most sites and has never run
    // on one left at its default. The translation is the fourth copy of the
    // same rule in the module; this asserts the copy, not the rule.
    $this->setActiveLoader('chasing_dots');

    $libraries = $this->alter(['loader' => []], 'neo_loader');

    $this->assertSame(
      ['dependencies' => ['neo_loader/plugin.chasing-dots']],
      $libraries['loader'],
      'The hook no longer writes the loader id\'s underscores as dashes.',
    );

    // Named explicitly, because the failure this guards against is a library
    // name that resolves to nothing: `hook_library_info_build()` keys the
    // built libraries by the dashed id, so an undashed dependency is a
    // dependency on a library that does not exist.
    $this->assertNotContains(
      'neo_loader/plugin.chasing_dots',
      $libraries['loader']['dependencies'],
      'The hook depends on the underscored loader id, which names no library.',
    );
  }

  /**
   * Tests that no dependency is added when no loader is named.
   */
  public function testAddsNoLoaderLibraryDependencyWhenNoLoaderIsNamedInTheSettings(): void {
    $this->setActiveLoader('');

    $libraries = $this->alter([
      'loader' => ['dependencies' => ['core/drupal']],
      'ajax' => [],
    ], 'neo_loader');

    $this->assertSame(
      ['core/drupal'],
      $libraries['loader']['dependencies'],
      'The hook added a loader-library dependency with no loader named.',
    );

    // And it adds no empty dependency list either: the assignment sits inside
    // the branch, so a library that declared none still declares none.
    $this->assertSame(
      ['loader' => ['dependencies' => ['core/drupal']], 'ajax' => []],
      $libraries,
      'The hook altered the module\'s libraries with no loader named.',
    );
  }

  /**
   * Tests that any other extension's libraries come back untouched.
   */
  public function testLeavesAnyOtherExtensionsLibrariesUntouched(): void {
    $this->expectNoSettingsLookup();

    // Both guards read the extension name and neither reads a library name,
    // so an extension whose libraries happen to be called `drupal.ajax` and
    // `loader` is still not core and still not this module. Naming them that
    // is the point of the case.
    $given = [
      'drupal.ajax' => ['dependencies' => ['core/drupal']],
      'loader' => ['dependencies' => ['core/drupal']],
      'something' => ['js' => ['some.js' => []]],
    ];

    $this->assertSame(
      $given,
      $this->alter($given, 'neo_modal'),
      'The hook altered an extension\'s libraries that are neither core\'s nor its own.',
    );
  }

  /**
   * Runs the module's library-info alter over a fabricated library array.
   *
   * @param array $libraries
   *   The libraries to hand the hook.
   * @param string $module
   *   The extension the libraries belong to.
   *
   * @return array
   *   What the hook made of them.
   */
  private function alter(array $libraries, string $module): array {
    neo_loader_library_info_alter($libraries, $module);
    return $libraries;
  }

  /**
   * Puts a container answering with the given active loader in place.
   *
   * @param string $loaderId
   *   The loader id the active settings answer with. The empty string is the
   *   "no loader named" answer, which is what the settings hold before one is
   *   chosen.
   */
  private function setActiveLoader(string $loaderId): void {
    $settings = $this->createMock(SettingsInterface::class);
    $settings->method('getValue')->with('loader')->willReturn($loaderId);
    $repository = $this->createMock(SettingsRepositoryInterface::class);
    $repository->method('getActive')->willReturn($settings);
    $this->setSettingsRepository($repository);
  }

  /**
   * Puts a container in place whose settings repository must not be consulted.
   */
  private function expectNoSettingsLookup(): void {
    $repository = $this->createMock(SettingsRepositoryInterface::class);
    $repository->expects($this->never())->method('getActive');
    $this->setSettingsRepository($repository);
  }

  /**
   * Puts a container holding the given settings repository in place.
   *
   * @param \Drupal\neo_settings\SettingsRepositoryInterface $repository
   *   The repository the hook reads the active settings from.
   */
  private function setSettingsRepository(SettingsRepositoryInterface $repository): void {
    $container = new ContainerBuilder();
    $container->set('neo_loader.settings', $repository);
    \Drupal::setContainer($container);
  }

}
