<?php

declare(strict_types=1);

namespace Drupal\Tests\neo_loader\Kernel;

use Drupal\neo_loader\LoaderManager;
use PHPUnit\Framework\Attributes\Group;

/**
 * Covers the manager answering a loader's stylesheet from its definition.
 *
 * The stylesheet used to be a plugin's business: every class rebuilt
 * `src/css/loader/{id}.css` by hand and the manager asked each instance for
 * the answer. Here the manager owns the rule, so the questions are which
 * definition key wins, what an extension other than `neo_loader` gets emitted
 * as, and what still happens for a class-based loader that declares nothing.
 *
 * The fixture module is what supplies a foreign extension, a declaration with
 * a `css` key of its own and a class plugin with neither — none of which
 * `neo_loader`'s own twelve can stand in for, because all twelve agree with
 * the derived path and all twelve are `neo_loader`'s.
 */
#[Group('neo_loader')]
final class LoaderStylesheetTest extends LoaderKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['neo_loader_test'];

  /**
   * Tests deriving the stylesheet of a definition that declares none.
   */
  public function testDerivesTheStylesheetWhenTheDefinitionDeclaresNone(): void {
    $definition = $this->loaderManager()->getDefinition('neo_loader_test_declaration');
    $this->assertSame('', $definition['css']);

    // Derived from the id inside the declaring extension, so the path is
    // relative and says nothing about who declared it.
    $this->assertSame(
      'src/css/loader/neo-loader-test-declaration.css',
      $this->loaderManager()->getCssFile('neo_loader_test_declaration'),
    );
  }

  /**
   * Tests a declared `css` path winning over the derived one.
   */
  public function testPrefersTheDeclaredStylesheetOverTheDerivedOne(): void {
    // The fixture declares a path that deliberately disagrees with the rule,
    // so agreeing with the rule here would be a failure rather than a pass.
    $this->assertNotSame(
      LoaderManager::deriveCssFile('neo_loader_test_declared_css'),
      'src/css/loader/declared.css',
    );
    $this->assertSame(
      'src/css/loader/declared.css',
      $this->loaderManager()->getCssFile('neo_loader_test_declared_css'),
    );
  }

  /**
   * Tests a foreign extension's derived stylesheet being emitted root-relative.
   */
  public function testEmitsForeignExtensionStylesheetRootRelative(): void {
    $libraries = neo_loader_library_info_build();
    $path = $this->container->get('extension.list.module')->getPath('neo_loader_test');

    // The library belongs to `neo_loader`, so a path relative to the declaring
    // extension would be resolved against the wrong one.
    $this->assertSame([
      'neo' => [
        'group' => 'contrib',
      ],
      'css' => [
        'base' => [
          '/' . $path . '/src/css/loader/neo-loader-test-declaration.css' => [],
        ],
      ],
    ], $libraries['plugin.neo-loader-test-declaration']);

    // `neo_loader`'s own stay relative, which is what keeps the twelve
    // shipped libraries exactly as they were.
    $this->assertSame(
      ['src/css/loader/wave.css' => []],
      $libraries['plugin.wave']['css']['base'],
    );
  }

  /**
   * Tests falling back to a class plugin that declares no `css`.
   */
  public function testFallsBackToTheClassPluginGetCssFile(): void {
    $definition = $this->loaderManager()->getDefinition('neo_loader_test_annotation');
    $this->assertSame('', $definition['css']);

    // The fixture's own `setCssFile()` answers a filename the derivation would
    // never produce, so a third-party loader's stylesheet staying exactly
    // where it is can be told apart from the rule quietly relocating it.
    $this->assertSame(
      'src/css/loader/neo-loader-test-annotation.css',
      LoaderManager::deriveCssFile('neo_loader_test_annotation'),
    );
    $this->assertSame(
      'src/css/loader/test-annotation.css',
      $this->loaderManager()->getCssFile('neo_loader_test_annotation'),
    );
  }

  /**
   * Tests the icon loader's stylesheet resolving without an instance.
   */
  public function testResolvesTheIconLoaderStylesheetWithoutInstantiatingIt(): void {
    // Declared on the attribute precisely so the class fallback is never
    // reached: instantiating `LoaderIcon` pulls `neo_icon`'s icon trait into
    // library discovery, which is half of what this change is for.
    $definition = $this->loaderManager()->getDefinition('icon');
    $this->assertSame('src/css/loader/icon.css', $definition['css']);

    $manager = new class(
      $this->container->get('container.namespaces'),
      $this->container->get('cache.discovery'),
      $this->container->get('module_handler'),
      $this->container->get('theme_handler'),
    ) extends LoaderManager {

      /**
       * {@inheritdoc}
       */
      public function createInstance($plugin_id, array $configuration = []) {
        throw new \LogicException('Instantiated ' . $plugin_id . ' to answer its stylesheet.');
      }

    };

    $this->assertSame('src/css/loader/icon.css', $manager->getCssFile('icon'));
  }

}
