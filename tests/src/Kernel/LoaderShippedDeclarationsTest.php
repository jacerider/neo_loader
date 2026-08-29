<?php

declare(strict_types=1);

namespace Drupal\Tests\neo_loader\Kernel;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Render\RenderContext;
use Drupal\neo_loader\LoaderDefault;
use Drupal\neo_loader\LoaderManager;
use Drupal\neo_loader\Plugin\Loader\LoaderIcon;
use PHPUnit\Framework\Attributes\Group;

/**
 * Covers the eleven inert loaders having become declarations.
 *
 * The module's own eleven markup-only loaders live in
 * `neo_loader.neo.loader.yml` rather than in eleven classes, and the icon
 * loader — the one whose markup is computed — stays a class. Nothing a site
 * can see moves: the same twelve ids, the same twelve labels, the same order
 * and the same markup byte for byte.
 *
 * The characterisation suite ticket 01 wrote is what proves the second half of
 * that, and it is deliberately untouched. This class asserts the first half —
 * that the eleven now come from the declaration file, through the default
 * loader class — which is what the pins on their own cannot tell apart from
 * eleven classes still being there.
 */
#[Group('neo_loader')]
final class LoaderShippedDeclarationsTest extends LoaderKernelTestBase {

  /**
   * Tests the eleven inert loaders coming from the module's declaration file.
   */
  public function testDiscoversTheElevenShippedLoadersFromTheDeclarationFile(): void {
    $file = DRUPAL_ROOT . '/'
      . $this->container->get('extension.list.module')->getPath('neo_loader')
      . '/neo_loader.neo.loader.yml';
    $this->assertFileExists($file);

    $declarations = Yaml::decode((string) file_get_contents($file));
    // Eleven entries and no twelfth: the icon loader is the one that is not
    // here, because its markup is computed rather than fixed.
    $this->assertSame(array_keys($this->declaredMarkup()), array_keys($declarations));

    $definitions = $this->loaderManager()->getDefinitions();
    foreach ($this->declaredMarkup() as $id => $markup) {
      $this->assertArrayHasKey($id, $definitions);
      $this->assertSame('neo_loader', $definitions[$id]['provider']);
      // The default loader class, so the definition carries the markup and
      // nothing has to be written to return it.
      $this->assertSame(LoaderDefault::class, $definitions[$id]['class']);
      $this->assertSame($markup, $definitions[$id]['markup']);
      $this->assertSame($markup, $declarations[$id]['markup']);
      $this->assertSame(
        (string) $definitions[$id]['label'],
        (string) $declarations[$id]['label'],
      );
      // None of the eleven declares a stylesheet: every one of them takes the
      // derived one, which is the rule that made eleven `setCssFile()` bodies
      // redundant in the first place.
      $this->assertArrayNotHasKey('css', $declarations[$id]);
    }
  }

  /**
   * Tests the eleven declarations rendering byte-identically to the classes.
   */
  public function testRendersTheElevenDeclaredLoadersByteForByte(): void {
    $rendered = [];
    foreach (array_keys($this->declaredMarkup()) as $id) {
      $instance = $this->loaderManager()->createInstance($id);
      // The markup arrives from the definition rather than from a class body,
      // which is the whole of what changed and the one thing ticket 01's pin
      // cannot see.
      $this->assertInstanceOf(LoaderDefault::class, $instance);

      $variables = $this->preprocess(['loader' => $id]);
      $rendered[$id] = (string) $variables['content']['loader']['#markup'];
    }

    $this->assertSame($this->declaredMarkup(), $rendered);
  }

  /**
   * Tests the twelve libraries being built without constructing a loader.
   */
  public function testBuildsTheTwelveLoaderLibrariesWithoutInstantiatingAnyLoader(): void {
    // A manager that refuses to construct anything. Asking for a stylesheet
    // used to cost one object per loader on every cache rebuild, and for the
    // icon loader that object drags `neo_icon`'s icon trait into library
    // discovery — so "without instantiating" is asserted by making an
    // instantiation impossible rather than by counting one.
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
        throw new \LogicException('Instantiated ' . $plugin_id . ' to build a library.');
      }

    };
    $this->container->set('plugin.manager.neo_loader', $manager);

    $this->assertSame($this->expectedLibraries(), neo_loader_library_info_build());
  }

  /**
   * Tests the option list's ids, labels and natural sort order not moving.
   */
  public function testKeepsTheOptionListIdsLabelsAndNaturalSortOrderUnchanged(): void {
    $options = array_map(
      static fn ($label): string => (string) $label,
      $this->loaderManager()->getLoaderOptionList(),
    );

    $this->assertSame([
      'chasing_dots' => 'Chasing dots',
      'circle' => 'Circle',
      // Pinned as shipped, misspelling included. This plan changes what a
      // loader is written as and nothing about what the settings form shows.
      'cube_grid' => 'Cube gird',
      'double_bounce' => 'Double bounce',
      'fading_circle' => 'Fading circle',
      'folding_cube' => 'Folding cube',
      'icon' => 'Icon',
      'pulse' => 'Pulse',
      'rotating_plane' => 'Rotating plane',
      'three_bounce' => 'Three bounce',
      'wandering_cubes' => 'Wandering cubes',
      'wave' => 'Wave',
    ], $options);

    // That order is now produced by a mixed set — eleven labels read from
    // YAML and one from an attribute — which is what tells "unchanged" apart
    // from "not moved yet". The sort is over the eleven declarations, and it
    // still lands them where their classes used to.
    $definitions = $this->loaderManager()->getDefinitions();
    foreach (array_keys($this->declaredMarkup()) as $id) {
      $this->assertSame(LoaderDefault::class, $definitions[$id]['class']);
    }
  }

  /**
   * The twelve library definitions, spelled out rather than derived.
   *
   * The same set ticket 01 pinned, restated here so that this criterion fails
   * on its own terms: a library built without instantiating anything is only
   * worth having if it is the library a site already had.
   *
   * @return array<string, array<string, mixed>>
   *   The libraries keyed by name, in discovery order.
   */
  private function expectedLibraries(): array {
    $names = [
      'chasing-dots',
      'circle',
      'cube-grid',
      'double-bounce',
      'fading-circle',
      'folding-cube',
      'icon',
      'pulse',
      'rotating-plane',
      'three-bounce',
      'wandering-cubes',
      'wave',
    ];
    $libraries = [];
    foreach ($names as $name) {
      $libraries['plugin.' . $name] = [
        'neo' => [
          'group' => 'contrib',
        ],
        'css' => [
          'base' => [
            'src/css/loader/' . $name . '.css' => [],
          ],
        ],
      ];
    }
    return $libraries;
  }

  /**
   * Tests the icon loader staying a class, with its computed markup unchanged.
   */
  public function testKeepsTheIconLoaderAsTheOneClassPlugin(): void {
    $directory = DRUPAL_ROOT . '/'
      . $this->container->get('extension.list.module')->getPath('neo_loader')
      . '/src/Plugin/Loader';

    // The one loader the class-based form exists for: its markup is computed
    // rather than fixed, so it is the one that cannot become a declaration.
    $this->assertSame(
      ['LoaderIcon.php'],
      array_values(array_filter(
        scandir($directory) ?: [],
        static fn (string $file): bool => str_ends_with($file, '.php'),
      )),
    );

    $definition = $this->loaderManager()->getDefinition('icon');
    $this->assertSame(LoaderIcon::class, $definition['class']);
    $this->assertSame('neo_loader', $definition['provider']);
    $this->assertSame('Icon', (string) $definition['label']);
    // Declared on the attribute, so nothing has to construct the plugin to
    // learn its stylesheet — which is what keeps the icon trait out of
    // library discovery.
    $this->assertSame('src/css/loader/icon.css', $definition['css']);

    $variables = $this->preprocess(['loader' => 'icon']);

    $this->assertInstanceOf(
      LoaderIcon::class,
      $this->loaderManager()->createInstance('icon'),
    );
    $this->assertSame(
      '<div class="animate-spin">Loading...</div>',
      (string) $variables['content']['loader']['#markup'],
    );
    $this->assertSame(
      ['neo-loader', 'neo-loader--type-icon'],
      $variables['attributes']['class'],
    );
  }

  /**
   * The markup each declared loader produces, byte for byte.
   *
   * Written out as literals, indentation included, and in the order the
   * declaration file writes them. These are ticket 01's pinned strings: the
   * point of the move is that they do not shift by a single space, so
   * deriving them from anything the module still owns would make the test
   * agree with whatever it became.
   *
   * @return array<string, string>
   *   The expected markup, keyed by loader id.
   */
  private function declaredMarkup(): array {
    return [
      'chasing_dots' => '<div class="neo-loader-style neo-loader-chasing-dots">
              <div class="neo-loader-child neo-loader-dot1"></div>
              <div class="neo-loader-child neo-loader-dot2"></div>
            </div>',
      'circle' => '<div class="neo-loader-style neo-loader-circle">
              <div class="neo-loader-circle1 neo-loader-child"></div>
              <div class="neo-loader-circle2 neo-loader-child"></div>
              <div class="neo-loader-circle3 neo-loader-child"></div>
              <div class="neo-loader-circle4 neo-loader-child"></div>
              <div class="neo-loader-circle5 neo-loader-child"></div>
              <div class="neo-loader-circle6 neo-loader-child"></div>
              <div class="neo-loader-circle7 neo-loader-child"></div>
              <div class="neo-loader-circle8 neo-loader-child"></div>
              <div class="neo-loader-circle9 neo-loader-child"></div>
              <div class="neo-loader-circle10 neo-loader-child"></div>
              <div class="neo-loader-circle11 neo-loader-child"></div>
              <div class="neo-loader-circle12 neo-loader-child"></div>
            </div>',
      'cube_grid' => '<div class="neo-loader-style neo-loader-cube-grid">
              <div class="neo-loader-cube neo-loader-cube1"></div>
              <div class="neo-loader-cube neo-loader-cube2"></div>
              <div class="neo-loader-cube neo-loader-cube3"></div>
              <div class="neo-loader-cube neo-loader-cube4"></div>
              <div class="neo-loader-cube neo-loader-cube5"></div>
              <div class="neo-loader-cube neo-loader-cube6"></div>
              <div class="neo-loader-cube neo-loader-cube7"></div>
              <div class="neo-loader-cube neo-loader-cube8"></div>
              <div class="neo-loader-cube neo-loader-cube9"></div>
            </div>',
      'double_bounce' => '<div class="neo-loader-style neo-loader-double-bounce">
                <div class="neo-loader-child neo-loader-double-bounce1"></div>
                <div class="neo-loader-child neo-loader-double-bounce2"></div>
            </div>',
      'fading_circle' => '<div class="neo-loader-style neo-loader-fading-circle">
              <div class="neo-loader-circle1 neo-loader-circle"></div>
              <div class="neo-loader-circle2 neo-loader-circle"></div>
              <div class="neo-loader-circle3 neo-loader-circle"></div>
              <div class="neo-loader-circle4 neo-loader-circle"></div>
              <div class="neo-loader-circle5 neo-loader-circle"></div>
              <div class="neo-loader-circle6 neo-loader-circle"></div>
              <div class="neo-loader-circle7 neo-loader-circle"></div>
              <div class="neo-loader-circle8 neo-loader-circle"></div>
              <div class="neo-loader-circle9 neo-loader-circle"></div>
              <div class="neo-loader-circle10 neo-loader-circle"></div>
              <div class="neo-loader-circle11 neo-loader-circle"></div>
              <div class="neo-loader-circle12 neo-loader-circle"></div>
            </div>',
      'folding_cube' => '<div class="neo-loader-style neo-loader-folding-cube">
              <div class="neo-loader-cube1 neo-loader-cube"></div>
              <div class="neo-loader-cube2 neo-loader-cube"></div>
              <div class="neo-loader-cube4 neo-loader-cube"></div>
              <div class="neo-loader-cube3 neo-loader-cube"></div>
            </div>',
      'pulse' => '<div class="neo-loader-style neo-loader-spinner neo-loader-spinner-pulse"></div>',
      'rotating_plane' => '<div class="neo-loader-style neo-loader-rotating-plane"></div>',
      'three_bounce' => '<div class="neo-loader-style neo-loader-three-bounce">
              <div class="neo-loader-child neo-loader-bounce1"></div>
              <div class="neo-loader-child neo-loader-bounce2"></div>
              <div class="neo-loader-child neo-loader-bounce3"></div>
            </div>',
      'wandering_cubes' => '<div class="neo-loader-style neo-loader-wandering-cubes">
              <div class="neo-loader-cube neo-loader-cube1"></div>
              <div class="neo-loader-cube neo-loader-cube2"></div>
            </div>',
      'wave' => '<div class="neo-loader-style neo-loader-wave">
              <div class="neo-loader-rect neo-loader-rect1"></div>
              <div class="neo-loader-rect neo-loader-rect2"></div>
              <div class="neo-loader-rect neo-loader-rect3"></div>
              <div class="neo-loader-rect neo-loader-rect4"></div>
              <div class="neo-loader-rect neo-loader-rect5"></div>
            </div>',
    ];
  }

  /**
   * Runs the theme hook's preprocessing over the declared default variables.
   *
   * The markup is asserted where a site sees it rather than only where it
   * comes from, so the assertion covers the whole path from the declaration
   * to the rendered string.
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
