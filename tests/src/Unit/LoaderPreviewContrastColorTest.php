<?php

declare(strict_types=1);

namespace Drupal\Tests\neo_loader\Unit;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\neo_loader\LoaderManagerInterface;
use Drupal\neo_loader\Settings\LoaderSettings;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Covers the loader preview wrapper carrying a real contrast colour.
 *
 * The preview wrapper exists to supply one thing — a colour — and the only
 * loader it can supply it to is the icon loader, which is declared
 * `color: inherit`. Every other loader paints its shapes with the
 * `--loader-text` the theme hook writes inline on the loader element inside
 * the wrapper, so an enclosing `color` is the wrapper's whole job.
 *
 * The colour it must name is the loader colour's own token with `-content`
 * appended, exactly as the theme hook spells it. The wrapper used to build a
 * Tailwind class instead, by splitting the colour value and splicing `content`
 * in front of the shade — a token name that is never emitted, in a class the
 * compiler never sees, since utilities here are generated from literals found
 * in scanned source. So two assertions guard the spelling rather than one: the
 * token the wrapper does name, and the inverted one it must not.
 *
 * The seam is the plugin built from doubles with no container at all. The
 * settings base constructor only rearranges the arrays it is handed, and both
 * of the plugin's collaborators are interfaces, so the built form array is a
 * direct assertion target.
 *
 * Nothing here asserts about anything else the form builder produces. The
 * throbber select, the three checkboxes, the loader-position textfield, the
 * colour element, the loader test button and its handlers are all built by the
 * same method and are all left alone.
 */
#[Group('neo_loader')]
final class LoaderPreviewContrastColorTest extends UnitTestCase {

  /**
   * Tests that it wraps the preview in a container carrying an inline colour.
   */
  public function testWrapsThePreviewInOneContainerCarryingAnInlineColour(): void {
    $preview = $this->previewWrapper(['loader' => 'wave', 'color' => 'base-900']);

    $this->assertSame(
      'container',
      $preview['#type'] ?? NULL,
      'The loader preview is not wrapped in a container element.',
    );
    $this->assertArrayHasKey(
      'style',
      $preview['#attributes'] ?? [],
      'The preview container carries no inline style.',
    );
    $this->assertStringStartsWith(
      'color:',
      $preview['#attributes']['style'],
      'The preview container inline style does not set a colour.',
    );
  }

  /**
   * Tests that it names the contrast colour as the value plus '-content'.
   *
   * @param string $color
   *   The loader colour value the plugin is configured with.
   */
  #[DataProvider('colorValues')]
  public function testNamesTheContrastColourAsTheValueWithContentAppended(string $color): void {
    $preview = $this->previewWrapper(['loader' => 'wave', 'color' => $color]);

    $this->assertSame(
      'color: rgb(var(--color-' . $color . '-content));',
      $preview['#attributes']['style'] ?? NULL,
      'The preview container does not name the contrast colour for ' . $color . '.',
    );
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
    $preview = $this->previewWrapper(['loader' => 'wave', 'color' => $color]);

    foreach ($this->ownStrings($preview) as $property => $value) {
      $this->assertStringNotContainsString(
        $pallet . '-content-' . $shade,
        $value,
        'The preview wrapper ' . $property . ' names the inverted contrast token.',
      );
      $this->assertStringNotContainsString(
        '-content-',
        $value,
        'The preview wrapper ' . $property . ' splices content before a shade.',
      );
    }
  }

  /**
   * Tests that it puts no class beginning 'text-' on the preview wrapper.
   */
  public function testPutsNoClassBeginningTextOnThePreviewWrapper(): void {
    $preview = $this->previewWrapper(['loader' => 'wave', 'color' => 'base-900']);

    foreach ($this->ownStrings($preview) as $property => $value) {
      $this->assertStringNotContainsString(
        'text-',
        $value,
        'The preview wrapper ' . $property . ' carries a text- utility class.',
      );
    }
  }

  /**
   * Tests that it omits the inline style entirely when the colour is empty.
   */
  public function testOmitsTheInlineStyleWhenTheColourIsEmpty(): void {
    $preview = $this->previewWrapper(['loader' => 'wave', 'color' => '']);

    $this->assertSame(
      'container',
      $preview['#type'] ?? NULL,
      'The loader preview is not wrapped in a container element.',
    );
    $this->assertArrayNotHasKey(
      'style',
      $preview['#attributes'] ?? [],
      'The preview container styles a token with an empty colour value in it.',
    );
  }

  /**
   * Tests that it still renders the active loader as the preview's content.
   */
  public function testStillRendersTheActiveLoaderAsThePreviewContent(): void {
    $preview = $this->previewWrapper(['loader' => 'circle', 'color' => 'base-900']);

    $loader = NULL;
    foreach (Element::children($preview) as $key) {
      if (($preview[$key]['#theme'] ?? NULL) === 'neo_loader') {
        $loader = $preview[$key];
      }
    }

    $this->assertIsArray(
      $loader,
      'The preview container has no neo_loader element as its content.',
    );
    $this->assertSame(
      'circle',
      $loader['#loader'] ?? NULL,
      'The preview does not render the configured loader.',
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
   * Builds the settings form and returns its loader preview wrapper.
   *
   * @param array $values
   *   The settings values the plugin reads through getValue().
   *
   * @return array
   *   The preview wrapper element.
   */
  private function previewWrapper(array $values): array {
    $form = $this->buildLoaderSettings($values)->buildSettingsForm(
      ['#parents' => []],
      $this->createMock(FormStateInterface::class),
    );

    $this->assertArrayHasKey(
      'preview',
      $form['wrapper'] ?? [],
      'The built settings form carries no loader preview.',
    );

    return $form['wrapper']['preview'];
  }

  /**
   * Returns the string leaves of an element's own properties.
   *
   * Only the wrapper's own '#'-prefixed properties, never its children, so
   * that what the wrapper itself declares is what is being read.
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
   * Builds the loader settings plugin from doubles and a values array.
   *
   * The plugin needs no container: the settings base constructor only
   * rearranges the configuration arrays it is handed, and the loader manager
   * and the admin context are both interfaces this test can double. The admin
   * context sees no route object, which nothing the preview does depends on.
   *
   * @param array $values
   *   The settings values the plugin reads through getValue().
   *
   * @return \Drupal\neo_loader\Settings\LoaderSettings
   *   The constructed plugin.
   */
  private function buildLoaderSettings(array $values): LoaderSettings {
    $route_match = $this->createMock(RouteMatchInterface::class);
    $route_match->method('getRouteObject')->willReturn(NULL);

    $settings = new LoaderSettings(
      [
        'config' => $values,
        'variation' => [],
        'variation_id' => '',
      ],
      'neo_loader',
      ['configuration' => []],
      $this->createMock(MessengerInterface::class),
      $this->createMock(FormBuilderInterface::class),
      $this->createMock(LoaderManagerInterface::class),
      new AdminContext($route_match),
    );
    $settings->setStringTranslation($this->getStringTranslationStub());

    return $settings;
  }

}
