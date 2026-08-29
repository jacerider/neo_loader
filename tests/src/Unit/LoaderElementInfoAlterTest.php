<?php

declare(strict_types=1);

namespace Drupal\Tests\neo_loader\Unit;

use Drupal\Core\Render\Element\Button;
use Drupal\Core\Render\Element\Container;
use Drupal\Core\Render\Element\Link;
use Drupal\Core\Render\Element\Select;
use Drupal\Core\Render\Element\Submit;
use Drupal\Core\Render\Element\Textfield;
use Drupal\Tests\UnitTestCase;
use Drupal\neo_loader\NeoLoaderPreRender;
use PHPUnit\Framework\Attributes\Group;

/**
 * Covers the rule that decides which element types get which pre-render.
 *
 * `neo_loader_element_info_alter()` is a pure array transform: it takes an
 * element-info array, prepends callbacks to some of its entries and hands it
 * back. Nothing in it reaches a service, so the rule is asserted against a
 * fabricated element-info array with no container at all. Whether a submit
 * button really is an input and whether the modal link type really exists are
 * different questions, about an installed site rather than about the rule, and
 * they are answered by the kernel tests beside this one.
 *
 * Two loops, in this order: four types named literally get the loader callback,
 * then every type declaring itself an input gets the autosubmit callback. A
 * type that is both — which button and submit are on a real site — therefore
 * ends up autosubmit, then loader, then whatever it already had, purely
 * because of the order the loops run in.
 *
 * This is characterisation. Every answer below is the module's answer today,
 * including the one the plan calls a defect: the first loop writes to each
 * named type with no check that the type exists, so a name absent from the
 * array is created rather than skipped.
 *
 * @see \Drupal\neo_loader\NeoLoaderPreRender
 * @see \Drupal\Tests\neo_loader\Kernel\LoaderElementRegistryTest
 */
#[Group('neo_loader')]
final class LoaderElementInfoAlterTest extends UnitTestCase {

  /**
   * Tests that the loader callback goes ahead of each named type's own.
   */
  public function testPrependsTheLoaderCallbackAheadOfTheExistingPreRendersOnEachNamedElementType(): void {
    // None of the four is marked as an input here, so only the first loop can
    // reach them. What a real submit button — an input as well as a name —
    // ends up with is the ordering criterion's, and the real registry's answer
    // is the kernel test's.
    $info = $this->alter([
      'link' => ['#pre_render' => [[Link::class, 'preRenderLink']]],
      'button' => ['#pre_render' => [[Button::class, 'preRenderButton']]],
      'submit' => ['#pre_render' => [[Submit::class, 'preRenderButton']]],
      'neo_modal_link' => [
        '#pre_render' => [['Drupal\neo_modal\Element\NeoModalLink', 'preRenderModalLink']],
      ],
    ]);

    $this->assertSame(
      [
        [NeoLoaderPreRender::class, 'loader'],
        [Link::class, 'preRenderLink'],
      ],
      $info['link']['#pre_render'],
      'The hook did not prepend the loader callback to the link element type.',
    );
    $this->assertSame(
      [
        [NeoLoaderPreRender::class, 'loader'],
        [Button::class, 'preRenderButton'],
      ],
      $info['button']['#pre_render'],
      'The hook did not prepend the loader callback to the button element type.',
    );
    $this->assertSame(
      [
        [NeoLoaderPreRender::class, 'loader'],
        [Submit::class, 'preRenderButton'],
      ],
      $info['submit']['#pre_render'],
      'The hook did not prepend the loader callback to the submit element type.',
    );
    $this->assertSame(
      [
        [NeoLoaderPreRender::class, 'loader'],
        ['Drupal\neo_modal\Element\NeoModalLink', 'preRenderModalLink'],
      ],
      $info['neo_modal_link']['#pre_render'],
      'The hook did not prepend the loader callback to the modal link element type.',
    );

    // The list is four names and no more: no other type in the array grew a
    // callback, which is what makes "named" a closed list rather than a
    // heuristic.
    $this->assertSame(
      ['link', 'button', 'submit', 'neo_modal_link'],
      array_keys($info),
      'The hook altered a set of element types other than the four it names.',
    );
  }

  /**
   * Tests that the autosubmit callback goes ahead of every input type's own.
   */
  public function testPrependsTheAutosubmitCallbackAheadOfTheExistingPreRendersOnEveryInputElementType(): void {
    // The second loop names nothing: it reads `#input` off each entry, so any
    // element type an extension declares as an input is covered by a rule
    // written before that extension existed.
    $info = $this->alter([
      'textfield' => [
        '#input' => TRUE,
        '#pre_render' => [[Textfield::class, 'preRenderTextfield']],
      ],
      'select' => [
        '#input' => TRUE,
        '#pre_render' => [[Select::class, 'preRenderSelect']],
      ],
      // An input carrying no pre-renders of its own still gets the callback,
      // because the hook merges onto `?? []` rather than onto a missing key.
      // The name is nobody's on purpose: the loop names no types at all, so an
      // element type an extension invents is covered by a rule written before
      // that extension existed.
      'some_extension_widget' => ['#input' => TRUE],
    ]);

    $this->assertSame(
      [
        [NeoLoaderPreRender::class, 'autosubmit'],
        [Textfield::class, 'preRenderTextfield'],
      ],
      $info['textfield']['#pre_render'],
      'The hook did not prepend the autosubmit callback to an input element type.',
    );
    $this->assertSame(
      [
        [NeoLoaderPreRender::class, 'autosubmit'],
        [Select::class, 'preRenderSelect'],
      ],
      $info['select']['#pre_render'],
      'The hook did not prepend the autosubmit callback to a second input element type.',
    );
    $this->assertSame(
      [[NeoLoaderPreRender::class, 'autosubmit']],
      $info['some_extension_widget']['#pre_render'],
      'The hook did not attach the autosubmit callback to an input carrying no pre-renders.',
    );

    // The autosubmit loop reaches only `#pre_render`: the `#input` flag it
    // read is handed back exactly as it arrived.
    $this->assertTrue(
      $info['textfield']['#input'],
      'The hook altered the input flag it branches on.',
    );
  }

  /**
   * Tests that a type that is neither named nor an input is untouched.
   */
  public function testLeavesAnElementTypeThatIsNeitherNamedNorAnInputUntouched(): void {
    // This is the answer behind "putting `#loader` on a container does
    // nothing": the container never receives the callback that would read the
    // property, so the property is inert rather than ignored.
    $container = [
      '#pre_render' => [[Container::class, 'preRenderContainer']],
      '#theme_wrappers' => ['container'],
    ];
    // A type declaring `#input => FALSE` is not declaring itself an input: the
    // loop gates on `!empty()`, so the flag being present is not enough. Which
    // callback it already carries is immaterial — what is asserted is that the
    // entry comes back exactly as it went in.
    $notAnInput = [
      '#input' => FALSE,
      '#pre_render' => [[Container::class, 'preRenderContainer']],
    ];

    $info = $this->alter([
      'container' => $container,
      'some_extension_marker' => $notAnInput,
    ]);

    $this->assertSame(
      $container,
      $info['container'],
      'The hook altered an element type that is neither named nor an input.',
    );
    $this->assertSame(
      $notAnInput,
      $info['some_extension_marker'],
      'The hook treated an element type declaring a falsy input flag as an input.',
    );
  }

  /**
   * Tests the callback order on a type that is both named and an input.
   */
  public function testOrdersTheAutosubmitCallbackBeforeTheLoaderCallbackOnTheTypeThatIsBoth(): void {
    // Button and submit are both, on every site. The ordering is not a
    // decision anybody wrote down — it falls out of the loader loop running
    // first and the autosubmit loop prepending onto its result — so it is
    // exactly the kind of thing that changes by accident when the two loops
    // are reordered or merged.
    $info = $this->alter([
      'submit' => [
        '#input' => TRUE,
        '#pre_render' => [[Submit::class, 'preRenderButton']],
      ],
    ]);

    $this->assertSame(
      [
        [NeoLoaderPreRender::class, 'autosubmit'],
        [NeoLoaderPreRender::class, 'loader'],
        [Submit::class, 'preRenderButton'],
      ],
      $info['submit']['#pre_render'],
      'The hook no longer orders autosubmit before loader before the element type\'s own pre-renders.',
    );
  }

  /**
   * Tests that a named type absent from the given element info is created.
   */
  public function testCreatesTheNamedElementTypeThatIsAbsentFromTheElementInfoItIsGiven(): void {
    // The defect this pins rather than fixes. The first loop writes
    // `$info[$type]['#pre_render']` with no check that `$type` exists, so a
    // name the site has no element for is fabricated into existence carrying a
    // pre-render and nothing else — no theme, no class, no `#type`. On a site
    // without the modal module, `neo_modal_link` is that name.
    $info = $this->alter(['textfield' => ['#input' => TRUE]]);

    $this->assertSame(
      ['#pre_render' => [[NeoLoaderPreRender::class, 'loader']]],
      $info['neo_modal_link'],
      'The hook no longer fabricates the modal link element type from nothing.',
    );

    // It is not special to the modal link: all four names are written
    // unconditionally, and the other three are only ever present because core
    // always provides them.
    foreach (['link', 'button', 'submit', 'neo_modal_link'] as $type) {
      $this->assertSame(
        ['#pre_render' => [[NeoLoaderPreRender::class, 'loader']]],
        $info[$type],
        'The hook no longer fabricates the ' . $type . ' element type from nothing.',
      );
    }

    // A fabricated type is not an input, so the second loop leaves it alone —
    // which is why the fabrication produces a pre-render and never a pair.
    $this->assertSame(
      [
        'textfield',
        'link',
        'button',
        'submit',
        'neo_modal_link',
      ],
      array_keys($info),
      'The hook created a set of element types other than the four it names.',
    );
  }

  /**
   * Runs the module's element-info alter over a fabricated element-info array.
   *
   * @param array $info
   *   The element info to hand the hook.
   *
   * @return array
   *   What the hook made of it.
   */
  private function alter(array $info): array {
    require_once __DIR__ . '/../../../neo_loader.module';
    neo_loader_element_info_alter($info);
    return $info;
  }

}
