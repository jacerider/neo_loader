<?php

declare(strict_types=1);

namespace Drupal\Tests\neo_loader\Unit;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\neo_loader\LoaderManagerInterface;
use Drupal\neo_loader\Settings\LoaderSettings;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Covers every gallery tile carrying a real contrast colour.
 *
 * A tile's loader container exists to supply one thing -- a colour -- and the
 * only loader it can supply it to is the icon loader, which is declared
 * `color: inherit`. Every other loader paints its shapes with the
 * `--loader-text` the theme hook writes inline on the loader element inside
 * the container, so an enclosing `color` is that container's whole job.
 *
 * The colour it must name is the loader colour's own token with `-content`
 * appended, exactly as the theme hook spells it. The old single preview
 * wrapper used to build a Tailwind class instead, by splitting the colour
 * value and splicing `content` in front of the shade -- a token name that is
 * never emitted, in a class the compiler never sees, since utilities here are
 * generated from literals found in scanned source. So two assertions guard the
 * spelling rather than one: the token the container does name, and the
 * inverted one it must not. Those two guards are why this test moved from the
 * preview to the gallery rather than dying with the preview.
 *
 * The colour goes on each tile's own container and not on the group, because
 * `color` inherits: one declaration on the gallery would also repaint the tile
 * captions in a colour chosen to be read on a near-black chip, against the
 * admin form's own light surface. So every assertion here is per tile, and the
 * gallery itself is asserted to declare nothing.
 *
 * The seam is the plugin built from doubles with no container at all. The
 * settings base constructor only rearranges the arrays it is handed, and both
 * of the plugin's collaborators are interfaces, so the built form array is a
 * direct assertion target.
 *
 * Nothing here asserts about anything else the form builder produces. The
 * three checkboxes, the loader-position textfield, the colour element, the
 * loader test button and its handlers are all built by the same method and are
 * all left alone.
 */
#[Group('neo_loader')]
final class LoaderGalleryContrastColorTest extends UnitTestCase {

  use LoaderSettingsConstructionTrait;

  /**
   * The loaders the doubled manager offers, keyed in the manager's own order.
   */
  private const OPTIONS = [
    'wave' => 'Alpha',
    'icon' => 'Bravo',
    'circle' => 'Charlie',
  ];

  /**
   * Tests that each tile wraps its loader in a container carrying a colour.
   */
  public function testWrapsEachTilesLoaderInOneContainerCarryingAnInlineColour(): void {
    foreach ($this->tileContainers('base-900') as $id => $container) {
      $this->assertSame(
        'container',
        $container['#type'] ?? NULL,
        'The loader of tile ' . $id . ' is not wrapped in a container element.',
      );
      $this->assertArrayHasKey(
        'style',
        $container['#attributes'] ?? [],
        'The loader container of tile ' . $id . ' carries no inline style.',
      );
      $this->assertStringStartsWith(
        'color:',
        $container['#attributes']['style'],
        'The loader container of tile ' . $id . ' does not set a colour.',
      );
    }
  }

  /**
   * Tests that it names the contrast colour as the value plus '-content'.
   *
   * @param string $color
   *   The loader colour value the plugin is configured with.
   */
  #[DataProvider('colorValues')]
  public function testNamesEachTilesContrastColourAsTheValueWithContentAppended(string $color): void {
    foreach ($this->tileContainers($color) as $id => $container) {
      $this->assertSame(
        'color: rgb(var(--color-' . $color . '-content));',
        $container['#attributes']['style'] ?? NULL,
        'The loader container of tile ' . $id
        . ' does not name the contrast colour for ' . $color . '.',
      );
    }
  }

  /**
   * Tests that it never names a token with 'content' placed before the shade.
   *
   * @param string $color
   *   The loader colour value the plugin is configured with.
   */
  #[DataProvider('colorValues')]
  public function testNeverNamesTheTokenWithContentBeforeTheShade(string $color): void {
    [$pallet, $shade] = explode('-', $color);

    foreach ($this->tileContainers($color) as $id => $container) {
      foreach ($this->ownStrings($container) as $property => $value) {
        $this->assertStringNotContainsString(
          $pallet . '-content-' . $shade,
          $value,
          'The loader container of tile ' . $id . ' ' . $property
          . ' names the inverted contrast token.',
        );
        $this->assertStringNotContainsString(
          '-content-',
          $value,
          'The loader container of tile ' . $id . ' ' . $property
          . ' splices content before a shade.',
        );
      }
    }
  }

  /**
   * Tests that it puts no class beginning 'text-' on any tile's container.
   */
  public function testPutsNoClassBeginningTextOnAnyTilesLoaderContainer(): void {
    foreach ($this->tileContainers('base-900') as $id => $container) {
      foreach ($this->ownStrings($container) as $property => $value) {
        $this->assertStringNotContainsString(
          'text-',
          $value,
          'The loader container of tile ' . $id . ' ' . $property
          . ' carries a text- utility class.',
        );
      }
    }
  }

  /**
   * Tests that it omits the inline style entirely when the colour is empty.
   */
  public function testOmitsTheInlineStyleWhenTheColourIsEmpty(): void {
    foreach ($this->tileContainers('') as $id => $container) {
      $this->assertSame(
        'container',
        $container['#type'] ?? NULL,
        'The loader of tile ' . $id . ' is not wrapped in a container element.',
      );
      $this->assertArrayNotHasKey(
        'style',
        $container['#attributes'] ?? [],
        'The loader container of tile ' . $id
        . ' styles a token with an empty colour value in it.',
      );
    }
  }

  /**
   * Tests that it declares no colour on the gallery the tiles sit in.
   *
   * `color` inherits, so a declaration here would reach the tile captions as
   * well as the loaders, and the captions are read against the admin theme's
   * own surface rather than against a loader chip.
   */
  public function testDeclaresNoColourOnTheGalleryItself(): void {
    $gallery = $this->gallery('base-900');

    $this->assertArrayNotHasKey(
      'style',
      $gallery['#attributes'] ?? [],
      'The gallery itself declares an inline style the captions inherit.',
    );
  }

  /**
   * The loader colour values the contrast colour is asserted for.
   *
   * The shipped default, plus two other pallet and shade pairs, because the
   * derivation has to hold for every value the colour element can produce and
   * not only for the one a compiled utility happens to exist for.
   *
   * @return array
   *   Each colour value, keyed by itself.
   */
  public static function colorValues(): array {
    return [
      'base-900' => ['base-900'],
      'primary-500' => ['primary-500'],
      'secondary-50' => ['secondary-50'],
    ];
  }

  /**
   * Builds the gallery and returns each tile's loader container.
   *
   * The container is found as the array holding the tile's rendered loader,
   * rather than by the property the gallery hangs it from, so that what is
   * asserted is the colour reaching that loader.
   *
   * @param string $color
   *   The loader colour value the plugin is configured with.
   *
   * @return array
   *   Each tile's loader container, keyed by loader id.
   */
  private function tileContainers(string $color): array {
    $gallery = $this->gallery($color);

    $containers = [];
    foreach (array_keys(self::OPTIONS) as $id) {
      $this->assertArrayHasKey(
        $id,
        $gallery,
        'The gallery carries no tile for ' . $id . '.',
      );
      $container = $this->loaderContainer($gallery[$id]);
      $this->assertIsArray(
        $container,
        'The tile for ' . $id . ' holds its loader in no container at all.',
      );
      $containers[$id] = $container;
    }

    return $containers;
  }

  /**
   * Returns the innermost array holding a rendered loader as a member.
   *
   * @param array $element
   *   The element to read.
   *
   * @return array|null
   *   The container, or NULL when the element holds no rendered loader.
   */
  private function loaderContainer(array $element): ?array {
    foreach ($element as $value) {
      if (!is_array($value)) {
        continue;
      }
      if (($found = $this->loaderContainer($value)) !== NULL) {
        return $found;
      }
      if (($value['#theme'] ?? NULL) === 'neo_loader') {
        return $element;
      }
    }

    return NULL;
  }

  /**
   * Returns the string leaves of an element's own properties.
   *
   * Only the container's own '#'-prefixed properties, never its children, so
   * that what the container itself declares is what is being read.
   *
   * @param array $element
   *   The element to read.
   *
   * @return array
   *   Each string, keyed by the property path it was found at.
   */
  private function ownStrings(array $element): array {
    $strings = [];
    foreach ($element as $key => $value) {
      if (!is_string($key) || !str_starts_with($key, '#')) {
        continue;
      }
      if (is_string($value)) {
        $strings[$key] = $value;
        continue;
      }
      if (is_array($value)) {
        foreach ($this->flatten($value) as $path => $leaf) {
          $strings[$key . $path] = $leaf;
        }
      }
    }

    return $strings;
  }

  /**
   * Flattens an array to its string leaves, keyed by bracketed key path.
   *
   * @param array $values
   *   The array to flatten.
   *
   * @return array
   *   Each string leaf, keyed by its key path.
   */
  private function flatten(array $values): array {
    $leaves = [];
    foreach ($values as $key => $value) {
      if (is_string($value)) {
        $leaves['[' . $key . ']'] = $value;
      }
      elseif (is_array($value)) {
        foreach ($this->flatten($value) as $path => $leaf) {
          $leaves['[' . $key . ']' . $path] = $leaf;
        }
      }
    }

    return $leaves;
  }

  /**
   * Builds the settings form and returns its loader gallery.
   *
   * @param string $color
   *   The loader colour value the plugin is configured with.
   *
   * @return array
   *   The gallery element.
   */
  private function gallery(string $color): array {
    $loader_manager = $this->createMock(LoaderManagerInterface::class);
    $loader_manager->method('getLoaderOptionList')->willReturn(self::OPTIONS);

    $settings = $this->buildLoaderSettings([
      'loader' => 'wave',
      'color' => $color,
      'hide_ajax_message' => FALSE,
      'always_fullscreen' => FALSE,
      'show_admin_paths' => TRUE,
      'loader_position' => 'body',
    ], NULL, $loader_manager);
    $settings->setStringTranslation($this->getStringTranslationStub());

    $build = new \ReflectionMethod(LoaderSettings::class, 'buildForm');
    $form = $build->invoke(
      $settings,
      ['#parents' => []],
      $this->createMock(FormStateInterface::class),
    );

    $this->assertArrayHasKey(
      'loader',
      $form,
      'The built settings form carries no loader gallery at its root.',
    );

    return $form['loader'];
  }

}
