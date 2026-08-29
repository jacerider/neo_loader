<?php

declare(strict_types=1);

namespace Drupal\Tests\neo_loader\Kernel;

use Drupal\Core\Render\RenderContext;
use Drupal\neo_loader\LoaderDefault;
use PHPUnit\Framework\Attributes\Group;

/**
 * Covers a loader declared as data rather than written as a class.
 *
 * Everything here is proved from a fixture extension pair — a module and a
 * theme — rather than from `neo_loader` itself, because the point of the
 * declaration file is that a site can write one. `neo_loader` declares nothing
 * in YAML yet, and the characterisation suite beside this one is what says its
 * own twelve are still exactly the twelve classes.
 *
 * The fixture theme is installed in `setUp()` rather than in the one method
 * that names it, so that every method here runs against a container in which
 * a theme has declared. A theme's declaration being dropped for a provider
 * that "does not exist" is the failure this arrangement makes loud.
 */
#[Group('neo_loader')]
final class LoaderDeclarationTest extends LoaderKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['neo_loader_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->container->get('theme_installer')->install(['neo_loader_test_theme']);
    $this->loaderManager()->clearCachedDefinitions();
  }

  /**
   * Tests a loader declared in a module's `neo.loader.yml` being discovered.
   */
  public function testDiscoversLoaderDeclaredByModule(): void {
    $definitions = $this->loaderManager()->getDefinitions();

    $this->assertArrayHasKey('neo_loader_test_declaration', $definitions);

    $definition = $definitions['neo_loader_test_declaration'];
    $this->assertSame('neo_loader_test_declaration', $definition['id']);
    $this->assertSame('Test declaration', (string) $definition['label']);
    $this->assertSame('neo_loader_test', $definition['provider']);
    $this->assertSame(
      '<div class="neo-loader-test-declaration"></div>',
      $definition['markup'],
    );
  }

  /**
   * Tests a declaration rendering through the default class and theme hook.
   */
  public function testRendersDeclaredLoaderThroughTheDefaultClass(): void {
    $instance = $this->loaderManager()->createInstance('neo_loader_test_declaration');

    $this->assertInstanceOf(LoaderDefault::class, $instance);
    $this->assertSame(
      '<div class="neo-loader-test-declaration"></div>',
      $instance->getMarkup(),
    );

    // The theme hook is the one place a site sees a loader at all, so the
    // markup is asserted where it lands rather than only where it comes from.
    $variables = $this->preprocess(['loader' => 'neo_loader_test_declaration']);

    $this->assertSame(
      '<div class="neo-loader-test-declaration"></div>',
      (string) $variables['content']['loader']['#markup'],
    );
    $this->assertSame(
      ['neo-loader', 'neo-loader--type-neo_loader_test_declaration'],
      $variables['attributes']['class'],
    );
    $this->assertSame(
      ['neo_loader/plugin.neo-loader-test-declaration'],
      $variables['content']['#attached']['library'],
    );
  }

  /**
   * Tests a loader declared by a theme being discovered as well.
   */
  public function testDiscoversLoaderDeclaredByTheme(): void {
    $definitions = $this->loaderManager()->getDefinitions();

    $this->assertArrayHasKey('neo_loader_test_theme_declaration', $definitions);

    $definition = $definitions['neo_loader_test_theme_declaration'];
    $this->assertSame('neo_loader_test_theme_declaration', $definition['id']);
    $this->assertSame('Test theme declaration', (string) $definition['label']);
    // The provider is a theme, which is the half of provider existence a
    // module-only check silently drops the definition for.
    $this->assertSame('neo_loader_test_theme', $definition['provider']);
    $this->assertSame(
      '<div class="neo-loader-test-theme-declaration"></div>',
      $this->loaderManager()
        ->createInstance('neo_loader_test_theme_declaration')
        ->getMarkup(),
    );
  }

  /**
   * Tests a declaration beating a class plugin that claims the same id.
   */
  public function testPrefersDeclarationOverClassClaimingTheSameId(): void {
    $definition = $this->loaderManager()->getDefinition('neo_loader_test_collision');

    // Every field is the declaration's, not the class's: label, class and
    // markup all come from the YAML entry, and `neo_loader_test`'s
    // `LoaderTestCollision` is nowhere in the answer.
    $this->assertSame('Test collision', (string) $definition['label']);
    $this->assertSame(LoaderDefault::class, $definition['class']);
    $this->assertSame(
      '<div class="neo-loader-test-collision-declaration"></div>',
      $definition['markup'],
    );

    $instance = $this->loaderManager()->createInstance('neo_loader_test_collision');
    $this->assertInstanceOf(LoaderDefault::class, $instance);
    $this->assertSame(
      '<div class="neo-loader-test-collision-declaration"></div>',
      $instance->getMarkup(),
    );
  }

  /**
   * Tests declared labels natural-sorting in among the class-based ones.
   */
  public function testNaturalSortsDeclaredLabelsAmongClassBasedOnes(): void {
    $options = array_map(
      static fn ($label): string => (string) $label,
      $this->loaderManager()->getLoaderOptionList(),
    );

    // One order, not two lists concatenated: the four declared labels and the
    // theme's fifth land between class-based neighbours on both sides. The
    // fixture file writes them out of order on purpose, so agreeing with the
    // file rather than sorting it would fail here.
    //
    // `Wave 2` before `Wave 10` is the natural half of the comparison; a
    // plain string sort puts `Wave 10` first.
    $this->assertSame([
      'chasing_dots' => 'Chasing dots',
      'circle' => 'Circle',
      'cube_grid' => 'Cube gird',
      'double_bounce' => 'Double bounce',
      'fading_circle' => 'Fading circle',
      'folding_cube' => 'Folding cube',
      'icon' => 'Icon',
      'pulse' => 'Pulse',
      'rotating_plane' => 'Rotating plane',
      'neo_loader_test_annotation' => 'Test annotation',
      'neo_loader_test_attribute' => 'Test attribute',
      'neo_loader_test_collision' => 'Test collision',
      'neo_loader_test_declaration' => 'Test declaration',
      'neo_loader_test_theme_declaration' => 'Test theme declaration',
      'three_bounce' => 'Three bounce',
      'wandering_cubes' => 'Wandering cubes',
      'wave' => 'Wave',
      'neo_loader_test_sort_2' => 'Wave 2',
      'neo_loader_test_sort_10' => 'Wave 10',
    ], $options);
  }

  /**
   * Runs the theme hook's preprocessing over the declared default variables.
   *
   * @param array<string, mixed> $overrides
   *   Variables to set in place of the declared defaults.
   *
   * @return array<string, mixed>
   *   The preprocessed variables.
   */
  private function preprocess(array $overrides = []): array {
    $registry = $this->container->get('theme.registry')->get();
    $variables = $overrides + $registry['neo_loader']['variables'];
    $this->container->get('renderer')->executeInRenderContext(
      new RenderContext(),
      static function () use (&$variables): void {
        template_preprocess_neo_loader($variables);
      },
    );
    return $variables;
  }

}
