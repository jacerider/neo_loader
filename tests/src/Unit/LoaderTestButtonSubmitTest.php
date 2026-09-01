<?php

declare(strict_types=1);

namespace Drupal\Tests\neo_loader\Unit;

use Drupal\Core\EventSubscriber\RedirectResponseSubscriber;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormSubmitter;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Utility\CallableResolver;
use Drupal\Tests\UnitTestCase;
use Drupal\neo_loader\LoaderManagerInterface;
use Drupal\neo_loader\Settings\LoaderSettings;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Covers the loader test button's submit handler.
 *
 * The handler shipped a twenty-second sleep under a comment claiming it was
 * "intentionally left empty", so every click of the control held a PHP worker
 * for twenty seconds. The handler is a static method that is handed a form
 * array and a form state and touches neither, so it is callable directly with
 * no container: these are the cheapest possible pins on it.
 *
 * The elapsed-time bound is one second — twenty times below the defect and
 * orders of magnitude above the cost of a method that does nothing, so it can
 * neither flake nor pass with a blocking call in place. The strict form-state
 * double carries no expectations, which is what proves the handler asks the
 * form state for nothing at all.
 */
#[Group('neo_loader')]
final class LoaderTestButtonSubmitTest extends UnitTestCase {

  use LoaderSettingsConstructionTrait;

  /**
   * Tests that both loader test controls name the empty submit handler.
   *
   * Both, because the property the handler protects is per control: a button
   * that omits it is a save button, and there are two of them now.
   */
  public function testNamesTheEmptySubmitHandlerOnBothLoaderTestControls(): void {
    $form = $this->buildSettingsForm();

    foreach (['fullscreen', 'throbber'] as $presentation) {
      $this->assertSame(
        [[LoaderSettings::class, 'submitLoaderSubmit']],
        $form['test'][$presentation]['#submit'] ?? NULL,
        "The $presentation loader test control does not name the empty submit "
        . 'handler, so clicking it saves the settings form.',
      );
    }
  }

  /**
   * Tests that a control omitting the handler would run the form's own.
   *
   * This is the counterfactual the empty handler exists for, and nothing above
   * states it: every other test here says what the handler does, not what
   * naming it in '#submit' buys. Core decides that in one place --
   * FormSubmitter::executeSubmitHandlers(), which runs the handlers the
   * triggering element supplied and falls back to the form's own only when
   * there are none -- so the dispatcher itself is the seam, and the fallback
   * is exercised rather than asserted about.
   *
   * A control that names the handler therefore leaves the settings form's own
   * handlers unrun, and one that omits it runs them, which on this form means
   * writing always_fullscreen and loader. That is the whole distance between
   * "test a presentation" and "save".
   *
   * @see \Drupal\Core\Form\FormBuilder::processForm()
   */
  public function testWouldRunTheSettingsFormsOwnSubmitHandlersWithoutTheDeclaration(): void {
    $submitter = $this->formSubmitter();
    $built = $this->buildSettingsForm();

    $ran = [];
    $form = [
      '#submit' => [
        static function (array &$form, FormStateInterface $form_state) use (&$ran): void {
          $ran[] = 'the settings form';
        },
      ],
    ];

    foreach (['fullscreen', 'throbber'] as $presentation) {
      $form_state = new FormState();
      $form_state->setSubmitHandlers($built['test'][$presentation]['#submit'] ?? []);
      $submitter->executeSubmitHandlers($form, $form_state);

      $this->assertSame(
        [],
        $ran,
        "Clicking the $presentation loader test control ran the settings "
        . "form's own submit handlers, which is a save.",
      );
    }

    // The same click from a control that named no handler of its own.
    $form_state = new FormState();
    $submitter->executeSubmitHandlers($form, $form_state);

    $this->assertSame(
      ['the settings form'],
      $ran,
      'A control naming no submit handler of its own did not run the settings '
      . "form's handlers either, so the '#submit' declaration on the two "
      . 'controls is no longer what stops a test click saving.',
    );
  }

  /**
   * Tests that it returns the control that was clicked from the callback.
   *
   * With one control the callback could name a fixed key path and be right;
   * with two it would answer for the wrong button half the time, and the ajax
   * response would replace the control the reader did not press. The clicked
   * control is the one core already recorded on the form state, so the
   * callback resolves it rather than being told where to look.
   */
  public function testReturnsTheControlThatWasClickedFromTheAjaxCallback(): void {
    $form = [
      'instance' => [
        'test' => [
          'fullscreen' => ['#id' => 'neo-loader-test-fullscreen'],
          'throbber' => ['#id' => 'neo-loader-test-throbber'],
        ],
      ],
    ];

    foreach (['fullscreen', 'throbber'] as $presentation) {
      $form_state = $this->createMock(FormStateInterface::class);
      $form_state->method('getTriggeringElement')->willReturn([
        '#array_parents' => ['instance', 'test', $presentation],
      ]);

      $this->assertSame(
        $form['instance']['test'][$presentation],
        LoaderSettings::ajaxLoaderTest($form, $form_state),
        "Clicking the $presentation control did not return that control, so "
        . 'the response replaces something other than the button that was '
        . 'pressed.',
      );
    }
  }

  /**
   * Tests that it returns from the submit handler in under one second.
   */
  public function testReturnsFromTheLoaderTestSubmitHandlerInUnderOneSecond(): void {
    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);

    $started = hrtime(TRUE);
    LoaderSettings::submitLoaderSubmit($form, $form_state);
    $elapsed = (hrtime(TRUE) - $started) / 1_000_000_000;

    $this->assertLessThan(
      1.0,
      $elapsed,
      'The loader test submit handler blocked for ' . $elapsed . ' seconds.',
    );
  }

  /**
   * Tests that it leaves the form array it was handed unchanged.
   */
  public function testLeavesTheFormArrayItWasHandedUnchanged(): void {
    $handed = [
      '#parents' => [],
      'instance' => [
        'test' => ['#type' => 'submit', '#value' => 'Test loader'],
      ],
    ];
    $form = $handed;
    $form_state = $this->createMock(FormStateInterface::class);

    LoaderSettings::submitLoaderSubmit($form, $form_state);

    $this->assertSame(
      $handed,
      $form,
      'The loader test submit handler altered the form array.',
    );
  }

  /**
   * Tests that it asks nothing of the form state.
   */
  public function testAsksNothingOfTheFormState(): void {
    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->expects($this->never())->method($this->anything());
    $form = [];

    LoaderSettings::submitLoaderSubmit($form, $form_state);
  }

  /**
   * Tests that the comment says why the body must stay empty.
   *
   * The criterion: the handler's comment states that the body must stay empty
   * because the button's '#submit' declaration is what stops a test click
   * saving the settings form.
   */
  public function testSaysInItsCommentWhyTheBodyMustStayEmpty(): void {
    $comment = $this->handlerComment();

    $this->assertStringNotContainsString(
      'Intentionally left empty',
      $comment,
      'The handler still carries the comment that hid the blocking call.',
    );
    $this->assertStringContainsString(
      '#submit',
      $comment,
      "The handler's comment does not name the '#submit' declaration.",
    );
    $this->assertMatchesRegularExpression(
      '/\bempty\b/i',
      $comment,
      "The handler's comment does not say the body stays empty.",
    );
    $this->assertMatchesRegularExpression(
      '/\bsav(?:e|es|ing)\b/i',
      $comment,
      "The handler's comment does not say what an empty body prevents.",
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
   * Builds a form submitter that runs handlers and touches no batch.
   *
   * The batch lookup the dispatcher makes before each handler resolves through
   * a procedural include this tier does not load, and an empty batch is what
   * every one of these clicks has anyway, so it is answered here rather than
   * bootstrapped.
   *
   * @return \Drupal\Core\Form\FormSubmitter
   *   The submitter.
   */
  private function formSubmitter(): FormSubmitter {
    $resolver = $this->createMock(CallableResolver::class);
    $resolver->method('getCallableFromDefinition')->willReturnArgument(0);

    return new class(
      new RequestStack(),
      $this->createMock(UrlGeneratorInterface::class),
      $this->createMock(RedirectResponseSubscriber::class),
      $resolver,
    ) extends FormSubmitter {

      /**
       * The batch the dispatcher is handed.
       *
       * @var array
       */
      protected $batch = [];

      /**
       * {@inheritdoc}
       */
      protected function &batchGet() {
        return $this->batch;
      }

    };
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
   * Returns the submit handler's docblock and body source.
   *
   * @return string
   *   The docblock and the lines of the handler, as written in the file.
   */
  private function handlerComment(): string {
    $method = new \ReflectionMethod(
      LoaderSettings::class,
      'submitLoaderSubmit',
    );
    $lines = (array) file((string) $method->getFileName());
    $body = array_slice(
      $lines,
      $method->getStartLine() - 1,
      $method->getEndLine() - $method->getStartLine() + 1,
    );

    return (string) $method->getDocComment() . "\n" . implode('', $body);
  }

}
