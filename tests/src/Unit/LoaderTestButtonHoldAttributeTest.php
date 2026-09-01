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
 * arithmetic, the loader gallery and its tiles, the three checkboxes and the
 * position textfield are all built by the same method and none of them is read
 * here — except by the confinement check, which reads every element in the
 * form precisely to prove the attribute reached none of them.
 */
#[Group('neo_loader')]
final class LoaderTestButtonHoldAttributeTest extends UnitTestCase {

  use LoaderSettingsConstructionTrait;

  /**
   * The attribute the control carries to ask for the hold.
   */
  private const HOLD_ATTRIBUTE = 'data-neo-loader-hold';

  /**
   * The attribute the inline control carries to pin its presentation.
   */
  private const PIN_ATTRIBUTE = 'data-neo-loader-presentation';

  /**
   * The two loader test controls, keyed by the presentation each produces.
   *
   * The keys are the presentation names the loader behaviour already takes,
   * which is also what the pin's values are and what the ajax progress types
   * are, so a control, its key, its progress type and — for the inline one —
   * its pin all say the same word.
   */
  private const CONTROLS = [
    'fullscreen' => [
      'label' => 'Test fullscreen overlay',
      'id' => 'neo-loader-test-fullscreen',
    ],
    'throbber' => [
      'label' => 'Test inline throbber',
      'id' => 'neo-loader-test-throbber',
    ],
  ];

  /**
   * Tests that it builds one loader test control per presentation.
   */
  public function testBuildsOneLoaderTestControlPerPresentation(): void {
    $pair = $this->buildSettingsForm()['test'];

    $controls = array_keys(array_filter(
      $pair,
      static fn (mixed $child, string|int $key): bool => is_string($key)
        && !str_starts_with($key, '#')
        && is_array($child),
      ARRAY_FILTER_USE_BOTH,
    ));

    $this->assertSame(
      ['fullscreen', 'throbber'],
      $controls,
      'The loader test is no longer one control per loader presentation, '
      . 'keyed by the presentation it produces.',
    );

    foreach ($controls as $key) {
      $this->assertSame(
        'submit',
        $pair[$key]['#type'] ?? NULL,
        'The ' . $key . ' loader test control is not a submit element.',
      );
    }
  }

  /**
   * Tests that it puts the hold attribute on both loader test controls.
   *
   * Both, because an unheld test is unreadable in either presentation: what a
   * request produces for seventy milliseconds is not something a person can
   * look at, and that is presentation-agnostic. ADR 0005 decided it for the
   * one control there was; nothing about it was about the overlay in
   * particular.
   */
  public function testPutsTheHoldAttributeOnBothLoaderTestControls(): void {
    $form = $this->buildSettingsForm();

    foreach (array_keys(self::CONTROLS) as $presentation) {
      $attributes = $form['test'][$presentation]['#attributes'] ?? [];

      $this->assertArrayHasKey(
        self::HOLD_ATTRIBUTE,
        $attributes,
        'The ' . $presentation . ' control carries no hold attribute.',
      );
      $this->assertTrue(
        $attributes[self::HOLD_ATTRIBUTE],
        'The hold attribute on the ' . $presentation
        . ' control is not a bare marker.',
      );
    }
  }

  /**
   * Tests that it puts the presentation pin on the inline control alone.
   *
   * The overlay control needs no pin: its declared progress type reaches a
   * path that never consults always_fullscreen. The inline one's path does, so
   * without the pin the throbber is unreachable on a site running the shipped
   * default -- which is the whole reason this control exists. The pin's value
   * is the presentation name show() already takes rather than a third
   * spelling of the same two things, and it rides on the element rather than
   * in the ajax options because ADR 0005 settled that question for the hold
   * and a second answer for the same shape of signal would be two
   * vocabularies for one idea.
   */
  public function testPutsThePresentationPinOnTheInlineControlAlone(): void {
    $form = $this->buildSettingsForm();

    $this->assertSame(
      'throbber',
      $form['test']['throbber']['#attributes'][self::PIN_ATTRIBUTE] ?? NULL,
      'The inline control carries no presentation pin naming the throbber, so '
      . 'clicking it on a site with "Always show loader as overlay" on '
      . 'produces the overlay the other control already shows.',
    );
    $this->assertArrayNotHasKey(
      self::PIN_ATTRIBUTE,
      $form['test']['fullscreen']['#attributes'],
      'The overlay control carries a presentation pin it has no use for.',
    );
  }

  /**
   * Tests that both controls name only the empty static submit handler.
   */
  public function testNamesOnlyTheEmptyStaticSubmitHandlerInBothControls(): void {
    $form = $this->buildSettingsForm();

    foreach (array_keys(self::CONTROLS) as $presentation) {
      $this->assertSame(
        [[LoaderSettings::class, 'submitLoaderSubmit']],
        $form['test'][$presentation]['#submit'] ?? NULL,
        "The $presentation control's '#submit' no longer names that handler "
        . 'alone, so clicking it runs the settings form\'s own submit '
        . 'handlers and saves.',
      );
    }

    $handler = new \ReflectionMethod(
      LoaderSettings::class,
      'submitLoaderSubmit',
    );
    $this->assertTrue(
      $handler->isStatic(),
      'The submit handler the controls name is not static.',
    );
    $this->assertSame(
      $handler->getStartLine() + 1,
      $handler->getEndLine(),
      'The submit handler the controls name no longer has an empty body.',
    );
  }

  /**
   * Tests that it leaves both controls' '#limit_validation_errors' empty.
   */
  public function testLeavesBothControlsLimitValidationErrorsEmpty(): void {
    $form = $this->buildSettingsForm();

    foreach (array_keys(self::CONTROLS) as $presentation) {
      $control = $form['test'][$presentation] ?? [];

      $this->assertArrayHasKey(
        '#limit_validation_errors',
        $control,
        "The $presentation control declares no '#limit_validation_errors'.",
      );
      $this->assertSame(
        [],
        $control['#limit_validation_errors'],
        "The $presentation control's '#limit_validation_errors' is no longer "
        . 'empty.',
      );
    }
  }

  /**
   * Tests that it declares the ajax progress type each presentation needs.
   *
   * This is the whole of the overlay control's mechanism and half of the
   * inline one's. Core turns the declared type into a call to the matching
   * setProgressIndicator* method, both of which this module overrides, so
   * 'fullscreen' reaches the overridden fullscreen path directly and never
   * consults always_fullscreen -- which is why the overlay is reachable on a
   * site that has turned the setting off. 'throbber' reaches the overridden
   * throbber path, which does consult it, and that is what the presentation
   * pin asserted below is for.
   */
  public function testDeclaresTheAjaxProgressTypeEachControlsPresentationNeeds(): void {
    $form = $this->buildSettingsForm();

    foreach (array_keys(self::CONTROLS) as $presentation) {
      $this->assertSame(
        $presentation,
        $form['test'][$presentation]['#ajax']['progress']['type'] ?? NULL,
        'The ' . $presentation . ' loader test control does not declare its '
        . 'ajax progress type, so which presentation it produces is left to '
        . 'the always_fullscreen setting rather than to the control.',
      );
    }
  }

  /**
   * Tests that it leaves each id, value, classes and ajax definition in place.
   */
  public function testLeavesEachControlsIdValueClassesAndAjaxDefinitionInPlace(): void {
    $form = $this->buildSettingsForm();

    foreach (self::CONTROLS as $presentation => $expected) {
      $control = $form['test'][$presentation];

      $this->assertSame(
        'submit',
        $control['#type'],
        'The ' . $presentation . ' control is no longer a submit element.',
      );
      $this->assertSame(
        $expected['id'],
        $control['#id'],
        "The $presentation control's id changed.",
      );
      $this->assertInstanceOf(
        TranslatableMarkup::class,
        $control['#value'],
        "The $presentation control's value is not translatable markup.",
      );
      $this->assertSame(
        $expected['label'],
        (string) $control['#value'],
        "The $presentation control's value changed.",
      );
      $this->assertSame(
        ['btn btn-xs'],
        $control['#attributes']['class'],
        "The $presentation control's classes changed.",
      );
      $this->assertSame(
        [
          'callback' => [LoaderSettings::class, 'ajaxLoaderTest'],
          'wrapper' => $expected['id'],
          'progress' => ['type' => $presentation],
        ],
        $control['#ajax'],
        "The $presentation control's ajax definition changed.",
      );
    }
  }

  /**
   * Tests that it puts the hold attribute on no other element in the form.
   */
  public function testPutsTheHoldAttributeOnNoOtherElementInTheSettingsForm(): void {
    $carriers = [];
    $this->collectCarriers(
      $this->buildSettingsForm(),
      self::HOLD_ATTRIBUTE,
      [],
      $carriers,
    );

    $this->assertSame(
      [['test', 'fullscreen'], ['test', 'throbber']],
      $carriers,
      'The hold attribute reached an element other than the two loader test '
      . 'controls: ' . $this->describeCarriers($carriers) . '.',
    );
  }

  /**
   * Tests that it puts the pin on no other element in the settings form.
   *
   * Like the hold, the pin is internal to the loader test: not a documented
   * affordance and not a surface a site may use. Carried by nothing else on
   * the form, it changes no other request on any page.
   */
  public function testPutsThePresentationPinOnNoOtherElementInTheSettingsForm(): void {
    $carriers = [];
    $this->collectCarriers(
      $this->buildSettingsForm(),
      self::PIN_ATTRIBUTE,
      [],
      $carriers,
    );

    $this->assertSame(
      [['test', 'throbber']],
      $carriers,
      'The presentation pin reached an element other than the inline loader '
      . 'test control: ' . $this->describeCarriers($carriers) . '.',
    );
  }

  /**
   * Tests that both progress-indicator paths ask for the hold.
   *
   * Which path runs is decided by a site setting the request has no say in.
   * A request that names no explicit progress type reaches core's throbber
   * indicator, and the override forwards that to its fullscreen one only while
   * "Always show loader as overlay" is on -- the default, and what the site
   * this was verified on stores. Turn the setting off and the same click takes
   * the throbber path instead.
   *
   * So a hold read on the fullscreen path alone is absent exactly where the
   * loader is least obtrusive and the demonstration is needed most, on every
   * installing site whose administrator turned that setting off, and no test
   * of the attribute or of the fullscreen path would say so. Both paths ask
   * the same helper, and this asserts both still do.
   */
  public function testAsksForTheHoldOnBothProgressIndicatorPaths(): void {
    $source = (string) file_get_contents(__DIR__ . '/../../../src/js/loader-ajax.js');

    $paths = [
      'setProgressIndicatorThrobber',
      'setProgressIndicatorFullscreen',
    ];
    foreach ($paths as $path) {
      $body = $this->progressIndicatorBody($source, $path);
      $this->assertStringContainsString(
        'holdIfRequested(this, element)',
        $body,
        "The $path override stopped asking for the hold, so a request that "
        . 'asks to hold its overlay no longer does on that path.',
      );
    }

    $this->assertStringContainsString(
      "hasAttribute('data-neo-loader-hold')",
      $source,
      'The ajax override stopped reading the attribute the loader test '
      . 'control carries, so nothing asks for the hold at all.',
    );
  }

  /**
   * Tests that dismissal leaves by the same exit teardown uses.
   *
   * The hold's whole purpose is to put the overlay in front of a person long
   * enough to be looked at, which makes dismissal the one way out anybody
   * watches -- every other overlay leaves while the reader is looking at the
   * response instead. An overlay that animated in and was then cut out of the
   * document in the tick the gesture arrived was the one place that showed.
   *
   * Both ways out now run the same exit, so this asserts the shared helper
   * exists, that each way out calls it, and that neither removes the node
   * itself -- a direct `.remove()` in either is how the two would drift apart
   * again, and it is invisible to every other test here.
   */
  public function testTakesTheOverlayDownThroughTheSharedExitOnBothWaysOut(): void {
    $source = (string) file_get_contents(__DIR__ . '/../../../src/js/loader.ts');

    $this->assertStringContainsString(
      'const runExit = (element:HTMLElement):void => {',
      $source,
      'The shared exit helper is gone, so the two ways out no longer share one.',
    );

    foreach (['dismissOverlay', 'hide'] as $way) {
      $body = $this->wayOutBody($source, $way);
      $this->assertMatchesRegularExpression(
        '/runExit\(\w+\)/',
        $body,
        "The $way way out stopped running the shared exit, so an overlay it "
        . 'takes down no longer animates away.',
      );
      $this->assertDoesNotMatchRegularExpression(
        '/^\s*\w+\.remove\(\);/m',
        $body,
        "The $way way out removes the overlay itself again rather than "
        . 'leaving it to the shared exit.',
      );
    }
  }

  /**
   * Returns the body of one of the two ways an overlay leaves the page.
   *
   * @param string $source
   *   The contents of the loader behaviour.
   * @param string $way
   *   Either the dismissal function or the teardown method.
   *
   * @return string
   *   That function's body, to its closing brace at its own indentation.
   */
  private function wayOutBody(string $source, string $way): string {
    $needles = [
      'dismissOverlay' => 'const dismissOverlay = (element?:HTMLElement|null):HTMLElement|null => {',
      'hide' => 'hide: (callback:Function, element?:HTMLElement|null) => {',
    ];
    $start = strpos($source, $needles[$way]);
    $this->assertNotFalse($start, "The $way way out is no longer declared.");

    $indent = $way === 'hide' ? '    ' : '  ';
    $end = strpos($source, "\n$indent}", (int) $start);
    return substr($source, (int) $start, (int) $end - (int) $start);
  }

  /**
   * Returns the body of one progress-indicator override.
   *
   * The two overrides are assigned onto the ajax prototype one after the
   * other, so an override's body runs from its own assignment to the next
   * assignment on that prototype, or to the end of the file for the last one.
   *
   * @param string $source
   *   The contents of the ajax override script.
   * @param string $method
   *   The name of the overridden progress-indicator method.
   *
   * @return string
   *   The body of that override.
   */
  private function progressIndicatorBody(string $source, string $method): string {
    $start = strpos($source, "Drupal.Ajax.prototype.$method = function ()");
    $this->assertNotFalse(
      $start,
      "The $method override is no longer assigned onto the ajax prototype.",
    );

    $end = strpos($source, 'Drupal.Ajax.prototype.', (int) $start + 1);
    return $end === FALSE
      ? substr($source, (int) $start)
      : substr($source, (int) $start, $end - (int) $start);
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
   * Collects the key path of every element carrying one attribute.
   *
   * @param array $element
   *   The element to read, and then its children.
   * @param string $attribute
   *   The attribute name to look for.
   * @param array $parents
   *   The key path that reached this element.
   * @param array $carriers
   *   The key paths collected so far, appended to in place.
   */
  private function collectCarriers(array $element, string $attribute, array $parents, array &$carriers): void {
    if (
      isset($element['#attributes'])
      && is_array($element['#attributes'])
      && array_key_exists($attribute, $element['#attributes'])
    ) {
      $carriers[] = $parents;
    }

    foreach ($element as $key => $child) {
      if (is_string($key) && !str_starts_with($key, '#') && is_array($child)) {
        $this->collectCarriers($child, $attribute, [...$parents, $key], $carriers);
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
