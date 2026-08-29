<?php

declare(strict_types=1);

namespace Drupal\Tests\neo_loader\Unit;

use Drupal\Core\Render\Markup;
use Drupal\Tests\UnitTestCase;
use Drupal\neo_loader\NeoLoaderPreRender;
use PHPUnit\Framework\Attributes\Group;

/**
 * Covers what the two element pre-render callbacks do to an element.
 *
 * Both callbacks are static, take an element array, return an element array
 * and reach no service, so they are directly callable with no container.
 *
 * The branch worth the most is the one the property's name hides: the
 * **loader message** is written only when `#loader` is a string or a markup
 * object, so `TRUE` and a render array both ask for a loader and both get no
 * message, and they arrive there down different roads.
 *
 * This is characterisation. Every answer below is the module's answer today,
 * including the ones the plan calls wrong.
 *
 * @see \Drupal\neo_loader\NeoLoaderPreRender
 */
#[Group('neo_loader')]
final class LoaderPreRenderAttachmentTest extends UnitTestCase {

  /**
   * Tests that the element comes back unchanged when no loader is asked for.
   */
  public function testReturnsTheElementUnchangedWhenNoLoaderIsAskedFor(): void {
    // An element with no `#loader` property at all is the common case: every
    // link, button and submit on a site goes through this callback.
    $bare = [
      '#type' => 'submit',
      '#value' => 'Save',
      '#attributes' => ['class' => ['button']],
      '#attached' => ['library' => ['some_extension/some-library']],
    ];
    $this->assertSame(
      $bare,
      NeoLoaderPreRender::loader($bare),
      'The callback altered an element carrying no loader property.',
    );

    // The gate is `!empty()`, so every falsy value means no loader — including
    // the empty render array and the string zero, which read as loader values
    // until the gate is looked at.
    foreach ([FALSE, NULL, '', '0', 0, []] as $value) {
      $element = ['#type' => 'submit', '#loader' => $value];
      $this->assertSame(
        $element,
        NeoLoaderPreRender::loader($element),
        'The callback altered an element whose loader value is falsy.',
      );
    }
  }

  /**
   * Tests that a loader attaches the loader library and the loader class.
   */
  public function testAttachesTheLoaderLibraryAndClassWhenTheLoaderIsAskedFor(): void {
    $element = NeoLoaderPreRender::loader([
      '#type' => 'submit',
      '#loader' => TRUE,
    ]);

    $this->assertSame(
      ['neo_loader/loader'],
      $element['#attached']['library'],
      'The callback did not attach the loader library.',
    );
    $this->assertSame(
      ['use-neo-loader'],
      $element['#attributes']['class'],
      'The callback did not attach the loader class.',
    );
  }

  /**
   * Tests that a string loader value is carried through as a loader message.
   */
  public function testCarriesTheStringLoaderValueThroughAsTheLoaderMessage(): void {
    $element = NeoLoaderPreRender::loader([
      '#type' => 'submit',
      '#loader' => 'Saving your changes',
    ]);

    $this->assertSame(
      'Saving your changes',
      $element['#attributes']['data-neo-loader-message'],
      'The callback did not carry a string loader value through as the loader message.',
    );
    // A message does not replace the loader itself: the string is a loader
    // value first and a message second.
    $this->assertSame(
      ['neo_loader/loader'],
      $element['#attached']['library'],
      'The callback did not attach the loader library alongside the message.',
    );
    $this->assertSame(
      ['use-neo-loader'],
      $element['#attributes']['class'],
      'The callback did not attach the loader class alongside the message.',
    );
  }

  /**
   * Tests that a markup loader value is carried through as a loader message.
   */
  public function testCarriesTheMarkupLoaderValueThroughAsTheLoaderMessage(): void {
    $markup = Markup::create('<em>Saving your changes</em>');
    $element = NeoLoaderPreRender::loader([
      '#type' => 'submit',
      '#loader' => $markup,
    ]);

    // The markup object is handed on untouched rather than cast to a string,
    // so whatever escaping it carries is still the attribute's to apply.
    $this->assertSame(
      $markup,
      $element['#attributes']['data-neo-loader-message'],
      'The callback did not carry a markup loader value through as the loader message.',
    );
    $this->assertSame(
      ['neo_loader/loader'],
      $element['#attached']['library'],
      'The callback did not attach the loader library alongside the message.',
    );
    $this->assertSame(
      ['use-neo-loader'],
      $element['#attributes']['class'],
      'The callback did not attach the loader class alongside the message.',
    );
  }

  /**
   * Tests that TRUE and a render array attach no loader message.
   */
  public function testAttachesNoLoaderMessageWhenTheLoaderValueIsTrueOrRenderArray(): void {
    // Asserted separately on purpose. Both values mean "show a loader, say
    // nothing", but they reach that answer down different roads: TRUE fails
    // `is_string()` and the markup check, a render array fails both as well
    // but would survive a future edit that started casting the value to a
    // string, which is the edit a test covering only the string case would
    // wave through.
    $fromTrue = NeoLoaderPreRender::loader([
      '#type' => 'submit',
      '#loader' => TRUE,
    ]);
    $this->assertArrayNotHasKey(
      'data-neo-loader-message',
      $fromTrue['#attributes'],
      'The callback attached a loader message for a loader value of TRUE.',
    );

    $fromRenderArray = NeoLoaderPreRender::loader([
      '#type' => 'submit',
      '#loader' => [
        '#theme' => 'neo_loader',
        '#loader' => 'wave',
      ],
    ]);
    $this->assertArrayNotHasKey(
      'data-neo-loader-message',
      $fromRenderArray['#attributes'],
      'The callback attached a loader message for a render array loader value.',
    );

    // Both still asked for a loader, which is the half that makes the silence
    // a branch rather than an early return.
    foreach ([$fromTrue, $fromRenderArray] as $element) {
      $this->assertSame(
        ['neo_loader/loader'],
        $element['#attached']['library'],
        'The callback did not attach the loader library for a message-less loader value.',
      );
      $this->assertSame(
        ['use-neo-loader'],
        $element['#attributes']['class'],
        'The callback did not attach the loader class for a message-less loader value.',
      );
    }
  }

  /**
   * Tests that both callbacks append to existing libraries and classes.
   */
  public function testAppendsToAnElementsExistingLibrariesAndClassesRatherThanReplacingThem(): void {
    // This is the difference between adding a loader to a button and silently
    // deleting the classes and libraries the button already carried, and both
    // callbacks are reached by the same button.
    $existing = [
      '#type' => 'submit',
      '#value' => 'Save',
      '#attributes' => ['class' => ['button', 'button--primary']],
      '#attached' => [
        'library' => ['some_extension/some-library', 'other_extension/other-library'],
      ],
    ];

    $withLoader = NeoLoaderPreRender::loader($existing + ['#loader' => TRUE]);
    $this->assertSame(
      [
        'some_extension/some-library',
        'other_extension/other-library',
        'neo_loader/loader',
      ],
      $withLoader['#attached']['library'],
      'The loader callback replaced the element\'s existing libraries.',
    );
    $this->assertSame(
      ['button', 'button--primary', 'use-neo-loader'],
      $withLoader['#attributes']['class'],
      'The loader callback replaced the element\'s existing classes.',
    );

    $withAutosubmit = NeoLoaderPreRender::autosubmit($existing + ['#autosubmit' => TRUE]);
    $this->assertSame(
      [
        'some_extension/some-library',
        'other_extension/other-library',
        'neo_loader/autosubmit',
      ],
      $withAutosubmit['#attached']['library'],
      'The autosubmit callback replaced the element\'s existing libraries.',
    );
    $this->assertSame(
      ['button', 'button--primary', 'use-neo-autosubmit'],
      $withAutosubmit['#attributes']['class'],
      'The autosubmit callback replaced the element\'s existing classes.',
    );

    // Everything the element carried that is neither a library nor a class is
    // handed back untouched.
    $this->assertSame('Save', $withLoader['#value']);
    $this->assertSame('Save', $withAutosubmit['#value']);
  }

  /**
   * Tests autosubmit's gate, and that exactly the two callbacks are trusted.
   */
  public function testAttachesAutosubmitOnlyWhenAskedForAndDeclaresExactlyTheTwoTrustedCallbacks(): void {
    $bare = [
      '#type' => 'textfield',
      '#attributes' => ['class' => ['form-text']],
      '#attached' => ['library' => ['some_extension/some-library']],
    ];
    $this->assertSame(
      $bare,
      NeoLoaderPreRender::autosubmit($bare),
      'The callback altered an element carrying no autosubmit property.',
    );
    foreach ([FALSE, NULL, '', '0', 0, []] as $value) {
      $element = ['#type' => 'textfield', '#autosubmit' => $value];
      $this->assertSame(
        $element,
        NeoLoaderPreRender::autosubmit($element),
        'The callback altered an element whose autosubmit value is falsy.',
      );
    }

    $element = NeoLoaderPreRender::autosubmit([
      '#type' => 'textfield',
      '#autosubmit' => TRUE,
    ]);
    $this->assertSame(
      ['neo_loader/autosubmit'],
      $element['#attached']['library'],
      'The callback did not attach the autosubmit library.',
    );
    $this->assertSame(
      ['use-neo-autosubmit'],
      $element['#attributes']['class'],
      'The callback did not attach the autosubmit class.',
    );
    // Autosubmit has no message branch of any kind: a string value is a
    // truthy value and nothing more.
    $this->assertSame(
      ['#type', '#autosubmit', '#attached', '#attributes'],
      array_keys(NeoLoaderPreRender::autosubmit([
        '#type' => 'textfield',
        '#autosubmit' => 'Submitting',
      ])),
      'The autosubmit callback wrote a property beyond the library and the class.',
    );

    // The cross-check. The callbacks the module registers in element info have
    // to be exactly the ones the class declares trusted, or the render
    // pipeline raises an untrusted-callback exception at the moment a loader
    // is asked for. Reading the registered names out of the hook rather than
    // restating them is what stops one of the pair being renamed alone.
    $trusted = NeoLoaderPreRender::trustedCallbacks();
    sort($trusted);
    $this->assertSame(
      ['autosubmit', 'loader'],
      $trusted,
      'The class no longer declares exactly the two pre-render callbacks as trusted.',
    );

    $registered = $this->registeredPreRenderMethods();
    $this->assertSame(
      $trusted,
      $registered,
      'The callbacks the module registers in element info are not the ones it declares trusted.',
    );
    foreach ($registered as $method) {
      $this->assertTrue(
        method_exists(NeoLoaderPreRender::class, $method),
        'The module registers a pre-render callback the class does not implement: ' . $method,
      );
    }
  }

  /**
   * Collects the pre-render method names the module registers in element info.
   *
   * @return string[]
   *   Every distinct method the module registers on the pre-render class,
   *   sorted, so that a rename in one place and not the other shows up.
   */
  private function registeredPreRenderMethods(): array {
    require_once __DIR__ . '/../../../neo_loader.module';

    // Enough of an element info array to reach both of the hook's loops: the
    // four types it names literally, and one type declaring itself an input.
    $info = [
      'link' => [],
      'button' => [],
      'submit' => [],
      'neo_modal_link' => [],
      'textfield' => ['#input' => TRUE],
      'container' => [],
    ];
    neo_loader_element_info_alter($info);

    $methods = [];
    foreach ($info as $element) {
      foreach ($element['#pre_render'] ?? [] as $callback) {
        if (is_array($callback) && ($callback[0] ?? NULL) === NeoLoaderPreRender::class) {
          $methods[$callback[1]] = $callback[1];
        }
      }
    }
    $methods = array_values($methods);
    sort($methods);
    return $methods;
  }

}
