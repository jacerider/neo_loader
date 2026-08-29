<?php

declare(strict_types=1);

namespace Drupal\Tests\neo_loader\Unit;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Tests\UnitTestCase;
use Drupal\neo_loader\LoaderManagerInterface;
use Drupal\neo_loader\Settings\LoaderSettings;
use PHPUnit\Framework\Attributes\Group;

/**
 * Covers the hold attribute the loader test control carries.
 *
 * The overlay the loader test raises is torn down the instant the round trip
 * lands, so the control demonstrates its subject by removing it. The fix asks
 * the progress override to hold that one overlay, and the only signal that
 * crosses to it is an attribute on the control itself: the override reads it
 * off the DOM element that triggered the request, which core's ajax object
 * already holds. Nothing is added to drupalSettings and no ajax option is
 * invented, so this element property is the whole of the PHP half.
 *
 * The attribute is internal. It is spelled to match the existing
 * data-neo-loader-message / -type / -delay family, but that is a naming
 * convention rather than a mechanism: those three are read by the click path
 * in the loader behaviour, on elements carrying use-neo-loader, and this one is
 * read by the ajax override off the ajax instance. The control is not a
 * loader-enabled element and does not become one, which is why nothing here
 * asserts it behaves like one.
 *
 * Adding an attribute is exactly the kind of edit that can disturb the two
 * properties the control's non-destructiveness rests on, and neither failure is
 * visible from a test of the submit handler alone. Naming the empty static
 * handler in '#submit' is what replaces the settings form's own submit handlers
 * for that click; break it and "Test loader" becomes "Save" on every installing
 * site. So both are asserted beside the attribute, along with the id, value,
 * classes and ajax callback that were not meant to move.
 *
 * The seam is the one the sibling tests use: the settings plugin is
 * constructible from doubles with no container at all, and the protected form
 * builder is reached by reflection and asserted against directly. The colour
 * arithmetic, the loader preview, the throbber select and its ajax, the three
 * checkboxes and the position textfield are all built by the same method and
 * none of them is read here — except by the confinement check, which reads
 * every element in the form precisely to prove the attribute reached none of
 * them.
 */
#[Group('neo_loader')]
final class LoaderTestButtonHoldAttributeTest extends UnitTestCase {

  use LoaderSettingsConstructionTrait;

  /**
   * The attribute the control carries to ask for the hold.
   */
  private const HOLD_ATTRIBUTE = 'data-neo-loader-hold';

  /**
   * The key path to the loader test control in the built form.
   */
  private const CONTROL_PARENTS = ['test'];

  /**
   * Tests that it puts the hold attribute on the loader test control.
   */
  public function testPutsTheHoldAttributeOnTheLoaderTestControl(): void {
    $control = $this->buildSettingsForm()['test'];

    $this->assertArrayHasKey(
      self::HOLD_ATTRIBUTE,
      $control['#attributes'],
      'The loader test control carries no hold attribute.',
    );
    $this->assertTrue(
      $control['#attributes'][self::HOLD_ATTRIBUTE],
      'The hold attribute on the loader test control is not a bare marker.',
    );
  }

  /**
   * Tests that it names only the empty static submit handler in '#submit'.
   */
  public function testNamesOnlyTheEmptyStaticSubmitHandlerInTheControlsSubmit(): void {
    $control = $this->buildSettingsForm()['test'];

    $this->assertSame(
      [[LoaderSettings::class, 'submitLoaderSubmit']],
      $control['#submit'],
      "The loader test control's '#submit' no longer names that handler alone.",
    );

    $handler = new \ReflectionMethod(
      LoaderSettings::class,
      'submitLoaderSubmit',
    );
    $this->assertTrue(
      $handler->isStatic(),
      'The submit handler the control names is not static.',
    );
    $this->assertSame(
      $handler->getStartLine() + 1,
      $handler->getEndLine(),
      'The submit handler the control names no longer has an empty body.',
    );
  }

  /**
   * Tests that it leaves the control's '#limit_validation_errors' empty.
   */
  public function testLeavesTheControlsLimitValidationErrorsEmpty(): void {
    $control = $this->buildSettingsForm()['test'];

    $this->assertArrayHasKey(
      '#limit_validation_errors',
      $control,
      "The loader test control declares no '#limit_validation_errors'.",
    );
    $this->assertSame(
      [],
      $control['#limit_validation_errors'],
      "The loader test control's '#limit_validation_errors' is no longer empty.",
    );
  }

  /**
   * Tests that it leaves the id, value, classes and ajax callback unchanged.
   */
  public function testLeavesTheControlsIdValueClassesAndAjaxCallbackUnchanged(): void {
    $control = $this->buildSettingsForm()['test'];

    $this->assertSame(
      'submit',
      $control['#type'],
      'The loader test control is no longer a submit element.',
    );
    $this->assertSame(
      'neo-loader-test',
      $control['#id'],
      "The loader test control's id changed.",
    );
    $this->assertInstanceOf(
      TranslatableMarkup::class,
      $control['#value'],
      "The loader test control's value is not translatable markup.",
    );
    $this->assertSame(
      'Test loader',
      (string) $control['#value'],
      "The loader test control's value changed.",
    );
    $this->assertSame(
      ['btn btn-xs'],
      $control['#attributes']['class'],
      "The loader test control's classes changed.",
    );
    $this->assertSame(
      [
        'callback' => [LoaderSettings::class, 'ajaxLoaderTest'],
        'wrapper' => 'neo-loader-test',
      ],
      $control['#ajax'],
      "The loader test control's ajax definition changed.",
    );
  }

  /**
   * Tests that it puts the hold attribute on no other element in the form.
   */
  public function testPutsTheHoldAttributeOnNoOtherElementInTheSettingsForm(): void {
    $carriers = [];
    $this->collectHoldCarriers($this->buildSettingsForm(), [], $carriers);

    $this->assertSame(
      [self::CONTROL_PARENTS],
      $carriers,
      'The hold attribute reached an element other than the loader test '
      . 'control: ' . $this->describeCarriers($carriers) . '.',
    );
  }

  /**
   * Tests that the settings plugin gains and loses no import.
   */
  public function testGainsAndLosesNoImportInTheSettingsPlugin(): void {
    $file = (new \ReflectionClass(LoaderSettings::class))->getFileName();
    preg_match_all(
      '/^use\s+([^;]+);$/m',
      (string) file_get_contents((string) $file),
      $matches,
    );

    $this->assertSame([
      'Drupal\Component\Utility\NestedArray',
      'Drupal\Core\Form\FormBuilderInterface',
      'Drupal\Core\Form\FormStateInterface',
      'Drupal\Core\Messenger\MessengerInterface',
      'Drupal\Core\Routing\AdminContext',
      'Drupal\neo_loader\LoaderManagerInterface',
      'Drupal\neo_settings\Plugin\SettingsBase',
      'Symfony\Component\DependencyInjection\ContainerInterface',
    ], $matches[1], 'The settings plugin gained or lost an import.');
  }

  /**
   * Builds the settings form through the plugin's protected form builder.
   *
   * @return array
   *   The built form.
   */
  private function buildSettingsForm(): array {
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
    $settings->setStringTranslation($this->getStringTranslationStub());

    $build = new \ReflectionMethod($settings, 'buildForm');

    return $build->invoke(
      $settings,
      ['#parents' => []],
      $this->createMock(FormStateInterface::class),
    );
  }

  /**
   * Collects the key path of every element carrying the hold attribute.
   *
   * @param array $element
   *   The element to read, and then its children.
   * @param array $parents
   *   The key path that reached this element.
   * @param array $carriers
   *   The key paths collected so far, appended to in place.
   */
  private function collectHoldCarriers(array $element, array $parents, array &$carriers): void {
    if (
      isset($element['#attributes'])
      && is_array($element['#attributes'])
      && array_key_exists(self::HOLD_ATTRIBUTE, $element['#attributes'])
    ) {
      $carriers[] = $parents;
    }

    foreach ($element as $key => $child) {
      if (is_string($key) && !str_starts_with($key, '#') && is_array($child)) {
        $this->collectHoldCarriers($child, [...$parents, $key], $carriers);
      }
    }
  }

  /**
   * Renders collected key paths for a failure message.
   *
   * @param array $carriers
   *   The key paths.
   *
   * @return string
   *   The key paths, each dot-joined, comma separated.
   */
  private function describeCarriers(array $carriers): string {
    return implode(', ', array_map(
      static fn (array $parents): string => implode('.', $parents) ?: '(the form itself)',
      $carriers,
    ));
  }

}
