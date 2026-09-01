<?php

declare(strict_types=1);

namespace Drupal\Tests\neo_loader\Unit;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Tests\UnitTestCase;
use Drupal\neo_loader\LoaderManagerInterface;
use Drupal\neo_loader\Settings\LoaderSettings;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Covers the loader gallery the settings form chooses the loader from.
 *
 * The form used to offer the loaders in a select and render one of them beside
 * it, re-rendering that one by ajax whenever the selection changed. Comparing
 * two loaders therefore cost a round trip and a memory of the last one looked
 * at. The gallery is the whole of the fix: every declared loader rendered at
 * once, each one selectable in place.
 *
 * So the assertions here are about the shape that makes comparison possible.
 * One option per definition the manager returns, in the manager's own order --
 * it sorts by label already, and re-sorting here would only disagree with it.
 * Each option rendering its own loader rather than the active one, which is the
 * assertion the old single preview would have failed and the reason the gallery
 * is not the preview with more markup around it. And the group defaulting to
 * the configured loader and marking itself required, because the value the
 * gallery carries is the same `loader` setting the select carried.
 *
 * The seam is the sibling tests': the settings plugin is constructible from
 * doubles with no container at all, and the protected form builder is reached
 * by reflection and asserted against directly. Nothing here reads the three
 * checkboxes, the position textfield, the colour element or the loader test
 * control, all of which the same method builds and none of which this covers.
 * The colour each tile paints its loader in has its own test beside this one.
 */
#[Group('neo_loader')]
final class LoaderGalleryTest extends UnitTestCase {

  use LoaderSettingsConstructionTrait;

  /**
   * Tests that it renders one selectable option per definition, in order.
   *
   * @param array $options
   *   The option list the doubled loader manager answers with.
   */
  #[DataProvider('definitionSets')]
  public function testRendersOneSelectableOptionPerDefinitionTheManagerReturns(array $options): void {
    $gallery = $this->gallery($options);

    $this->assertSame(
      'radios',
      $gallery['#type'] ?? NULL,
      'The loader gallery is not a group of radio controls.',
    );
    $this->assertSame(
      $options,
      $gallery['#options'] ?? [],
      'The gallery options are not the manager definitions in the '
      . "manager's own order.",
    );
    $this->assertSame(
      array_keys($options),
      Element::children($gallery),
      'The gallery does not carry exactly one option element per definition, '
      . 'keyed by loader id.',
    );
  }

  /**
   * Tests that each option renders its own loader, not the active one.
   *
   * The old preview rendered the configured loader and only the configured
   * loader, so it is this assertion -- and only this one -- that separates a
   * gallery from the preview with the select still beside it.
   *
   * @param array $options
   *   The option list the doubled loader manager answers with.
   */
  #[DataProvider('definitionSets')]
  public function testRendersEachOptionsOwnLoaderRatherThanTheActiveLoader(array $options): void {
    $active = array_key_first($options);
    $gallery = $this->gallery($options, ['loader' => $active]);

    foreach (array_keys($options) as $id) {
      $this->assertArrayHasKey(
        $id,
        $gallery,
        'The gallery carries no option element for ' . $id . '.',
      );
      $this->assertSame(
        $id,
        $this->renderedLoaderId($gallery[$id]),
        'The option ' . $id . ' renders the loader '
        . var_export($this->renderedLoaderId($gallery[$id]), TRUE)
        . ' rather than its own.',
      );
    }
  }

  /**
   * Tests that it defaults to the configured loader and is required.
   */
  public function testDefaultsTheGalleryToTheConfiguredLoaderAndMarksItRequired(): void {
    $gallery = $this->gallery(
      ['wave' => 'Alpha', 'circle' => 'Bravo', 'pulse' => 'Charlie'],
      ['loader' => 'pulse'],
    );

    $this->assertSame(
      'pulse',
      $gallery['#default_value'] ?? NULL,
      'The gallery does not default to the configured loader.',
    );
    $this->assertTrue(
      $gallery['#required'] ?? FALSE,
      'The gallery is not marked required.',
    );
  }

  /**
   * Tests that the select, its wrapper and its ajax callback are all gone.
   *
   * The wrapper existed to re-render one preview when the select changed, and
   * its '#parents' override was already putting the value where the gallery
   * now sits. The callback goes with it rather than being deprecated: it is a
   * form ajax callback on a final settings plugin, meaningful only to the
   * element that named it.
   */
  public function testBuildsNoThrobberSelectAndHangsNoAjaxOnTheLoaderValue(): void {
    $options = ['wave' => 'Alpha', 'circle' => 'Bravo', 'pulse' => 'Charlie'];
    $form = $this->builtForm($options);

    $this->assertArrayHasKey(
      'loader',
      $form,
      'The loader value does not sit at the settings root.',
    );
    $this->assertArrayNotHasKey(
      'wrapper',
      $form,
      'The built settings form still carries the loader wrapper.',
    );

    $selects = [];
    $this->collectElementsOfType($form, 'select', [], $selects);
    $this->assertSame(
      [],
      $selects,
      'The built settings form still builds a select: '
      . implode(', ', array_map(
        static fn (array $parents): string => implode('.', $parents),
        $selects,
      )) . '.',
    );

    $this->assertArrayNotHasKey(
      '#ajax',
      $form['loader'],
      'The loader value still hangs an ajax callback.',
    );
    $this->assertFalse(
      method_exists(LoaderSettings::class, 'ajaxLoaderChange'),
      'The settings plugin still declares the loader-change ajax callback.',
    );
  }

  /**
   * Tests that the gallery attaches the module's own loader library itself.
   *
   * The library reaches this page today anyway, because core's ajax library is
   * made to depend on the ajax override which depends on it. That is not a
   * dependency the gallery may rest on: a form that renders loaders should not
   * be styled only because another control on it happens to bring core's ajax
   * along.
   */
  public function testAttachesTheModulesOwnLoaderLibrary(): void {
    $gallery = $this->gallery(
      ['wave' => 'Alpha', 'circle' => 'Bravo', 'pulse' => 'Charlie'],
    );

    $this->assertContains(
      'neo_loader/loader',
      $gallery['#attached']['library'] ?? [],
      'The gallery attaches no loader library of its own.',
    );
  }

  /**
   * The definition sets the gallery is asserted against.
   *
   * One set smaller than the twelve loaders the module ships and one larger,
   * because a site that declares its own loaders moves the count in the
   * direction no shipped set can reach. Both are keyed in an order their keys
   * do not sort into, so that preserving the manager's order is what the
   * assertion reads rather than an accident of the key names.
   *
   * @return array
   *   Each option list, keyed by how to name it in a failure message.
   */
  public static function definitionSets(): array {
    return [
      'fewer than twelve' => [
        [
          'wave' => 'Alpha',
          'circle' => 'Bravo',
          'pulse' => 'Charlie',
        ],
      ],
      'more than twelve' => [self::fifteenDefinitions()],
    ];
  }

  /**
   * Builds an option list of fifteen loaders, keyed in reverse key order.
   *
   * @return array
   *   The option list.
   */
  private static function fifteenDefinitions(): array {
    $options = [];
    foreach (range(15, 1) as $index) {
      $options['loader_' . $index] = 'Loader ' . $index;
    }

    return $options;
  }

  /**
   * Returns the loader id the one rendered loader inside an element names.
   *
   * The loader is carried as the option's field prefix rather than as a child
   * element, so this reads the option's own properties as well as its
   * children.
   *
   * @param array $element
   *   The element to read.
   *
   * @return string|null
   *   The loader id, or NULL when the element renders no loader at all.
   */
  private function renderedLoaderId(array $element): ?string {
    if (($element['#theme'] ?? NULL) === 'neo_loader') {
      return $element['#loader'] ?? NULL;
    }

    foreach ($element as $value) {
      if (is_array($value) && ($found = $this->renderedLoaderId($value)) !== NULL) {
        return $found;
      }
    }

    return NULL;
  }

  /**
   * Collects the key path of every element built as a given type.
   *
   * @param array $element
   *   The element to read, and then its children.
   * @param string $type
   *   The element type to look for.
   * @param array $parents
   *   The key path that reached this element.
   * @param array $found
   *   The key paths collected so far, appended to in place.
   */
  private function collectElementsOfType(array $element, string $type, array $parents, array &$found): void {
    if (($element['#type'] ?? NULL) === $type) {
      $found[] = $parents;
    }

    foreach ($element as $key => $child) {
      if (is_string($key) && !str_starts_with($key, '#') && is_array($child)) {
        $this->collectElementsOfType($child, $type, [...$parents, $key], $found);
      }
    }
  }

  /**
   * Builds the settings form and returns its loader gallery.
   *
   * @param array $options
   *   The option list the doubled loader manager answers with.
   * @param array $values
   *   Settings values to override the defaults with.
   *
   * @return array
   *   The gallery element.
   */
  private function gallery(array $options, array $values = []): array {
    $form = $this->builtForm($options, $values);

    $this->assertArrayHasKey(
      'loader',
      $form,
      'The built settings form carries no loader gallery at its root.',
    );

    return $form['loader'];
  }

  /**
   * Builds the settings form through the plugin's protected form builder.
   *
   * @param array $options
   *   The option list the doubled loader manager answers with.
   * @param array $values
   *   Settings values to override the defaults with.
   *
   * @return array
   *   The built form.
   */
  private function builtForm(array $options, array $values = []): array {
    $loader_manager = $this->createMock(LoaderManagerInterface::class);
    $loader_manager->method('getLoaderOptionList')->willReturn($options);

    $settings = $this->buildLoaderSettings($values + [
      'loader' => array_key_first($options),
      'color' => 'base-900',
      'hide_ajax_message' => FALSE,
      'always_fullscreen' => FALSE,
      'show_admin_paths' => TRUE,
      'loader_position' => 'body',
    ], NULL, $loader_manager);
    $settings->setStringTranslation($this->getStringTranslationStub());

    $build = new \ReflectionMethod(LoaderSettings::class, 'buildForm');

    return $build->invoke(
      $settings,
      ['#parents' => []],
      $this->createMock(FormStateInterface::class),
    );
  }

}
