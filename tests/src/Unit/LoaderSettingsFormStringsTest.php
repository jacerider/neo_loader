<?php

declare(strict_types=1);

namespace Drupal\Tests\neo_loader\Unit;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\neo_loader\LoaderManagerInterface;
use Drupal\neo_loader\Settings\LoaderSettings;
use PHPUnit\Framework\Attributes\Group;

/**
 * Covers the settings form's strings resolving through its own translator.
 *
 * The settings form built ten of its strings with the global t() while four
 * strings beside them used the trait method. The two are not equivalent, which
 * is why this is a behavioural pin rather than a style one: the global function
 * hands the markup no translator, so the markup resolves one from the global
 * container the moment it is rendered, while the trait method hands it the
 * translation service injected into the plugin.
 *
 * So the seam is a plugin with a translation stub and no global container at
 * all. Rendering a string the global function built asks \Drupal for a service
 * and there is nothing to answer; rendering one the trait method built asks the
 * stub. Both halves of that are asserted, because "it did not throw" alone
 * would pass if the strings stopped being translatable markup altogether.
 *
 * The form builder is protected and is called by reflection. The public
 * settings-form entry point would build the same elements, but only after the
 * base class's scope and variation handling, none of which this plan touches.
 * The base class's own form builder returns the form unchanged, so the only
 * collaborators left are the loader manager double and the values array.
 *
 * The form builder runs lines this test does not own: the gallery tiles and
 * the colour arithmetic behind them, and the loader test button and its ajax
 * callback. Nothing here asserts about any of them. Every assertion is confined
 * to the titles and descriptions of the loader gallery, the three checkboxes
 * and the loader-position textfield — which is exactly the ten strings.
 */
#[Group('neo_loader')]
final class LoaderSettingsFormStringsTest extends UnitTestCase {

  use LoaderSettingsConstructionTrait;

  /**
   * The prefix the marking translation stub stamps on every string it renders.
   *
   * Nothing else can produce it, so a rendered string carrying it can only have
   * come through the translation service the plugin was handed.
   */
  private const MARKER = 'translated-by-the-injected-service:';

  /**
   * The element properties every claimed element carries.
   *
   * The fixture holds each expected string under the same name without its
   * hash, so that phpcs does not read a table of expected strings as a form
   * element that forgot to translate its own description.
   */
  private const PROPERTIES = ['#title', '#description'];

  /**
   * Tests that it builds the settings form with no global container set.
   */
  public function testBuildsTheSettingsFormWithNoGlobalContainerSet(): void {
    $this->assertFalse(
      \Drupal::hasContainer(),
      'A global container was already set before the form was built.',
    );

    $form = $this->buildSettingsForm($this->getStringTranslationStub());

    foreach ($this->claimedElements() as $label => $claimed) {
      $element = $this->elementAt($form, $claimed['parents'], $label);
      foreach (self::PROPERTIES as $property) {
        $this->assertNotSame(
          '',
          (string) $element[$property],
          'The ' . $property . ' of ' . $label . ' resolved to nothing.',
        );
      }
    }

    $this->assertFalse(
      \Drupal::hasContainer(),
      'Building and reading the form set a global container.',
    );
  }

  /**
   * Tests that every label and description resolves through the injection.
   */
  public function testResolvesEveryLabelAndDescriptionThroughTheInjectedService(): void {
    $form = $this->buildSettingsForm($this->markingTranslation());

    foreach ($this->claimedElements() as $label => $claimed) {
      $element = $this->elementAt($form, $claimed['parents'], $label);
      foreach (self::PROPERTIES as $property) {
        $this->assertSame(
          self::MARKER . $claimed[ltrim($property, '#')],
          (string) $element[$property],
          'The ' . $property . ' of ' . $label
          . ' did not resolve through the translation service the plugin holds.',
        );
      }
    }
  }

  /**
   * Tests that each string keeps its text, placeholders and options.
   */
  public function testLeavesEachStringsTextPlaceholdersAndOptionsUnchanged(): void {
    $form = $this->buildSettingsForm($this->getStringTranslationStub());

    foreach ($this->claimedElements() as $label => $claimed) {
      $element = $this->elementAt($form, $claimed['parents'], $label);
      foreach (self::PROPERTIES as $property) {
        $markup = $element[$property];
        $this->assertInstanceOf(
          TranslatableMarkup::class,
          $markup,
          'The ' . $property . ' of ' . $label . ' is not translatable markup.',
        );
        $this->assertSame(
          $claimed[ltrim($property, '#')],
          (string) $markup,
          'The ' . $property . ' of ' . $label . ' changed its text.',
        );
        $this->assertSame(
          [],
          $markup->getArguments(),
          'The ' . $property . ' of ' . $label . ' gained a placeholder.',
        );
        $this->assertSame(
          [],
          $markup->getOptions(),
          'The ' . $property . ' of ' . $label . ' gained an option.',
        );
      }
    }
  }

  /**
   * Tests that the settings plugin makes no call to the global t().
   *
   * The criterion is phpcs reporting no "t() calls should be avoided in
   * classes" warning for this file. The sniff fires on a call to t() that is
   * not a method call, which is what this looks for, so the warning cannot come
   * back in a tier that does not run phpcs.
   */
  public function testMakesNoCallToTheGlobalTranslateFunction(): void {
    $file = (string) (new \ReflectionClass(LoaderSettings::class))->getFileName();
    $found = preg_match_all(
      '/(?<![\w>:$])t\s*\(/',
      (string) file_get_contents($file),
    );

    $this->assertSame(
      0,
      $found,
      'The settings plugin calls the global t() ' . $found . ' time(s).',
    );
  }

  /**
   * Builds the settings form through the plugin's protected form builder.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The translation service to inject into the plugin.
   *
   * @return array
   *   The built form.
   */
  private function buildSettingsForm(TranslationInterface $translation): array {
    $loader_manager = $this->createMock(LoaderManagerInterface::class);
    $loader_manager->method('getLoaderOptionList')->willReturn([
      'circle' => 'Circle',
    ]);

    $settings = $this->buildLoaderSettings([
      'loader' => 'circle',
      'color' => 'base-content',
      'hide_ajax_message' => FALSE,
      'always_fullscreen' => FALSE,
      'show_admin_paths' => TRUE,
      'loader_position' => 'body',
    ], NULL, $loader_manager);
    $settings->setStringTranslation($translation);

    $build = new \ReflectionMethod($settings, 'buildForm');

    return $build->invoke(
      $settings,
      ['#parents' => []],
      $this->createMock(FormStateInterface::class),
    );
  }

  /**
   * Returns a translation service that marks everything it is asked to render.
   *
   * @return \Drupal\Core\StringTranslation\TranslationInterface
   *   The translation service.
   */
  private function markingTranslation(): TranslationInterface {
    $translation = $this->createMock(TranslationInterface::class);
    $translation->method('translateString')->willReturnCallback(
      static fn (TranslatableMarkup $markup): string => self::MARKER . $markup->getUntranslatedString(),
    );

    return $translation;
  }

  /**
   * Returns the element at a key path, failing if the form has none there.
   *
   * @param array $form
   *   The built form.
   * @param array $parents
   *   The key path to the element.
   * @param string $label
   *   How to name the element in a failure message.
   *
   * @return array
   *   The element.
   */
  private function elementAt(array $form, array $parents, string $label): array {
    $exists = FALSE;
    $element = NestedArray::getValue($form, $parents, $exists);
    $this->assertTrue($exists, 'The built form carries no ' . $label . '.');

    return $element;
  }

  /**
   * Returns the elements this plan claims, with the strings they carry.
   *
   * The loader gallery, the three checkboxes and the loader-position
   * textfield — the ten strings the global t() built, and nothing else. The
   * colour element, the gallery tiles and the loader test button are all
   * built by the same method and are all left alone here.
   *
   * @return array
   *   Each claimed element's key path and its expected title and description,
   *   keyed by how to name it in a failure message.
   */
  private function claimedElements(): array {
    return [
      'the loader gallery' => [
        'parents' => ['loader'],
        'title' => 'Throbber',
        'description' => 'Choose your loader',
      ],
      'the hide-ajax-message checkbox' => [
        'parents' => ['hide_ajax_message'],
        'title' => 'Never show ajax loading message',
        'description' => 'Choose whether you want to hide the loading ajax message even when it is set.',
      ],
      'the always-fullscreen checkbox' => [
        'parents' => ['always_fullscreen'],
        'title' => 'Always show loader as overlay (fullscreen)',
        'description' => 'Choose whether you want to show the loader as an overlay, no matter what the settings of the loader are.',
      ],
      'the admin-paths checkbox' => [
        'parents' => ['show_admin_paths'],
        'title' => 'Use ajax loader on admin pages',
        'description' => 'Choose whether you also want to show the loader on admin pages or still like to use the default core loader.',
      ],
      'the loader-position textfield' => [
        'parents' => ['loader_position'],
        'title' => 'Loader position',
        'description' => 'Allows you to change the position where the loader is inserted. A valid css selector must be used here. The default value is: body',
      ],
    ];
  }

}
