<?php

declare(strict_types=1);

namespace Drupal\Tests\neo_loader\Unit;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\neo_loader\Settings\LoaderSettings;
use PHPUnit\Framework\Attributes\Group;

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
