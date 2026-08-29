<?php

declare(strict_types=1);

namespace Drupal\Tests\neo_loader\Kernel;

use Drupal\Core\Render\RenderContext;
use Drupal\Core\Url;
use PHPUnit\Framework\Attributes\Group;

/**
 * Pins what the `neo_loader` theme hook's preprocessing does today.
 *
 * `template_preprocess_neo_loader()` is the one place a site can see the
 * loader plugins at all: it is what turns a plugin id into markup, into a type
 * class, into an attached library and into the two colour custom properties.
 * The markup is asserted as exact strings rather than as shapes, because a
 * whitespace change inside a throbber is a change to what a CSS selector binds
 * to.
 *
 * `neo_icon` is installed here and nowhere else in the suite: the icon loader
 * is the only shipped loader that computes its markup, and it computes it by
 * asking `neo_icon` for a spinner. That call also needs a render context,
 * which is why every preprocess call in this test runs inside one — outside a
 * context the renderer throws, and `IconElement`'s `__toString()` turns the
 * throw into a fatal.
 */
#[Group('neo_loader')]
final class LoaderPreprocessTest extends LoaderKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['neo_icon'];

  /**
   * Tests that every shipped loader's markup survives byte for byte.
   */
  public function testRendersEveryShippedLoadersMarkupByteForByte(): void {
    $rendered = [];
    foreach (array_keys($this->shippedMarkup()) as $id) {
      $variables = $this->preprocess(['loader' => $id]);
      $rendered[$id] = (string) $variables['content']['loader']['#markup'];
    }

    $this->assertSame($this->shippedMarkup(), $rendered);
  }

  /**
   * Tests the type class, the attached library and the colour properties.
   */
  public function testAppliesTypeClassLibraryAndColourProperties(): void {
    // Colour unset. The theme hook declares '' as the default, so an unset
    // colour is an empty string, and the empty string falls through to the
    // stored setting.
    $unset = $this->preprocess(['loader' => 'three_bounce']);

    $this->assertSame(
      ['neo-loader', 'neo-loader--type-three_bounce'],
      $unset['attributes']['class'],
    );
    $this->assertSame(
      ['neo_loader/plugin.three-bounce'],
      $unset['content']['#attached']['library'],
    );
    $this->assertSame(
      '--loader-bg: rgb(var(--color-base-900)); '
      . '--loader-text: rgb(var(--color-base-900-content));',
      $unset['attributes']['style'],
    );

    // Colour set. The named colour is used in place of the stored one, and
    // the content property is the same name with `-content` appended.
    $set = $this->preprocess(['loader' => 'wave', 'color' => 'primary-500']);

    $this->assertSame(
      '--loader-bg: rgb(var(--color-primary-500)); '
      . '--loader-text: rgb(var(--color-primary-500-content));',
      $set['attributes']['style'],
    );

    // Colour explicitly FALSE. The one value that suppresses the fallback:
    // no style attribute is written at all, so the stylesheet's own colours
    // stand.
    $false = $this->preprocess(['loader' => 'wave', 'color' => FALSE]);

    $this->assertArrayNotHasKey('style', $false['attributes']);
    $this->assertSame(
      ['neo-loader', 'neo-loader--type-wave'],
      $false['attributes']['class'],
    );
  }

  /**
   * Tests the active-loader fallback and the resolved ajax target.
   */
  public function testFallsBackToActiveLoaderAndResolvesAjaxTarget(): void {
    // Stored before the first call, because the settings repository resolves
    // the active settings once and caches them for the rest of the request.
    $this->config('neo_loader.settings')->set('loader', 'pulse')->save();

    $fallback = $this->preprocess(['loader' => '']);

    $this->assertSame('pulse', $fallback['loader']);
    $this->assertSame(
      ['neo-loader', 'neo-loader--type-pulse'],
      $fallback['attributes']['class'],
    );
    // No ajax target, so none of the ajax branch fires.
    $this->assertArrayNotHasKey('id', $fallback['attributes']);
    $this->assertArrayNotHasKey('data-loader-url', $fallback['attributes']);
    $this->assertArrayNotHasKey('#attached', $fallback);

    // A string ajax target is used as given, and the element is given a
    // generated id so the behaviour has something to bind to.
    $string = $this->preprocess([
      'loader' => 'wave',
      'ajax' => '/neo-loader/refresh',
    ]);

    $this->assertMatchesRegularExpression(
      '/^neo-loader-[0-9a-f]{13}$/',
      $string['attributes']['id'],
    );
    $this->assertSame('/neo-loader/refresh', $string['attributes']['data-loader-url']);
    $this->assertSame(
      ['neo-loader', 'neo-loader--type-wave', 'neo-loader-ajax'],
      $string['attributes']['class'],
    );
    $this->assertSame(['neo_loader/ajax'], $string['#attached']['library']);

    // An id the caller supplied is kept rather than regenerated.
    $identified = $this->preprocess([
      'loader' => 'wave',
      'ajax' => '/neo-loader/refresh',
      'attributes' => ['id' => 'my-loader'],
    ]);

    $this->assertSame('my-loader', $identified['attributes']['id']);

    // A Url object is resolved to its string, in place, before it is written
    // to the attribute.
    $url = Url::fromUri('https://example.com/neo-loader/refresh');
    $resolved = $this->preprocess(['loader' => 'wave', 'ajax' => $url]);

    $this->assertSame(
      'https://example.com/neo-loader/refresh',
      $resolved['ajax'],
    );
    $this->assertSame(
      'https://example.com/neo-loader/refresh',
      $resolved['attributes']['data-loader-url'],
    );
  }

  /**
   * Runs the theme hook's preprocessing over the declared default variables.
   *
   * The defaults are read from the theme registry rather than restated, so
   * that `hook_theme()`'s own declared defaults are part of what is pinned:
   * the `loader` default in particular is why the stored-setting fallback is
   * only reachable by naming an empty loader.
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

  /**
   * The markup each shipped loader produces, byte for byte.
   *
   * Written out as literals, indentation included. The point of the criterion
   * is that these bytes do not move, so deriving them from anything the
   * module still owns would make the test agree with whatever it became.
   *
   * The icon loader is the one entry that is not a fixed block: it asks
   * `neo_icon` for a spinner and wraps the answer. What `neo_loader` owns is
   * the wrapper, and what the icon resolves to is `neo_icon`'s business — in
   * a container with no icon library installed it degrades to the plain text
   * the loader passed in.
   *
   * @return array<string, string>
   *   The expected markup, keyed by loader id.
   */
  private function shippedMarkup(): array {
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
      'icon' => '<div class="animate-spin">Loading...</div>',
    ];
  }

}
