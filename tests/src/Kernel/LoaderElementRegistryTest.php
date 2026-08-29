<?php

declare(strict_types=1);

namespace Drupal\Tests\neo_loader\Kernel;

use Drupal\Core\Render\Element\Link;
use Drupal\Core\Render\Element\Submit;
use Drupal\Core\Render\RendererInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\neo_loader\NeoLoaderPreRender;
use PHPUnit\Framework\Attributes\Group;

/**
 * Pins where the module's pre-render callbacks land on a real site.
 *
 * The rule the element-info alter applies is asserted against a fabricated
 * array in the unit test beside this one. What that array cannot answer is
 * whether the rule's inputs are what the module assumes: whether a submit
 * button really declares itself an input, whether a link really does not, and
 * whether the modal link element type really exists. Those are facts about an
 * installed site, so they are asserted here against the real element registry
 * with the modal module enabled.
 *
 * The last test renders. Everything else in this plan proves a callback does
 * the right thing or is registered in the right place, and neither is proof
 * that the render pipeline reaches it — a callback registered under a name the
 * class does not declare trusted is registered exactly as well as one that
 * works, right up to the moment something asks for a loader.
 *
 * @see \Drupal\Tests\neo_loader\Unit\LoaderElementInfoAlterTest
 * @see \Drupal\neo_loader\NeoLoaderPreRender
 */
#[Group('neo_loader')]
final class LoaderElementRegistryTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   *
   * `neo_modal` is here because it declares the fourth of the four element
   * types the hook names, and only with it enabled does that name refer to a
   * real element. `neo_settings` is its own dependency — a kernel test enables
   * exactly the modules it lists rather than resolving the chain a site
   * installs through. `neo_icon` is `neo_loader`'s, for the icon loader's
   * class. `neo_tooltip` is deliberately absent: it alters the link element
   * too, and this test is about one module's contribution to that list.
   */
  protected static $modules = [
    'system',
    'neo_settings',
    'neo_icon',
    'neo_modal',
    'neo_loader',
  ];

  /**
   * Tests which callbacks the real element registry carries, and on what.
   */
  public function testCarriesBothCallbacksOnTheSubmitButtonTheLoaderOnlyOnTheLinkAndNeitherOnTheContainer(): void {
    // Submit is both: core declares it a form element, which makes it an
    // input, and the hook names it literally. The order is the one the two
    // loops produce, and core's own pre-render stays last — the module adds a
    // loader to a button rather than replacing what makes it a button.
    $this->assertSame(
      [
        [NeoLoaderPreRender::class, 'autosubmit'],
        [NeoLoaderPreRender::class, 'loader'],
        [Submit::class, 'preRenderButton'],
      ],
      $this->preRender('submit'),
      'The real element registry no longer carries both callbacks, in that order, on a submit button.',
    );

    // Link is named but is not an input, so it gets one of the pair. This is
    // the half the fabricated array cannot assert: nothing but the real
    // registry says whether core marks a link as an input.
    $this->assertSame(
      [
        [NeoLoaderPreRender::class, 'loader'],
        [Link::class, 'preRenderLink'],
      ],
      $this->preRender('link'),
      'The real element registry no longer carries the loader callback alone on a link.',
    );

    // Container is neither, which is the registry's statement of why a
    // `#loader` on a container does nothing at all.
    foreach ($this->preRender('container') as $callback) {
      $this->assertNotSame(
        NeoLoaderPreRender::class,
        is_array($callback) ? ($callback[0] ?? NULL) : NULL,
        'The real element registry carries one of the module\'s callbacks on a container.',
      );
    }

    // And the modal link, with the module that owns it installed, is a real
    // element type carrying the loader callback ahead of its own — not the
    // fabricated stub a site without that module gets.
    $modalLink = $this->preRender('neo_modal_link');
    $this->assertSame(
      [NeoLoaderPreRender::class, 'loader'],
      $modalLink[0],
      'The real element registry no longer carries the loader callback first on the modal link.',
    );
    $this->assertGreaterThan(
      1,
      count($modalLink),
      'The modal link element type carries no pre-render of its own, so the modal module is not installed.',
    );
  }

  /**
   * Tests that a rendered submit button asking for a loader comes out with one.
   */
  public function testRendersTheSubmitButtonWithTheLoaderClassMessageAndLibrary(): void {
    $build = [
      '#type' => 'submit',
      '#value' => 'Save',
      '#loader' => 'Saving your changes',
    ];

    $renderer = $this->container->get('renderer');
    assert($renderer instanceof RendererInterface);
    $html = (string) $renderer->renderRoot($build);

    $this->assertStringContainsString(
      'use-neo-loader',
      $html,
      'The rendered submit button carries no loader class.',
    );
    $this->assertStringContainsString(
      'data-neo-loader-message="Saving your changes"',
      $html,
      'The rendered submit button carries no loader message attribute.',
    );
    // The library rides the element's `#attached`, which bubbles to the root
    // of the render as the markup is built.
    $this->assertContains(
      'neo_loader/loader',
      $build['#attached']['library'] ?? [],
      'The rendered submit button attached no loader library.',
    );

    // A submit button that asks for nothing gets nothing, so the class above
    // is the property's doing rather than the element type's.
    $plain = ['#type' => 'submit', '#value' => 'Save'];
    $this->assertStringNotContainsString(
      'use-neo-loader',
      (string) $renderer->renderRoot($plain),
      'A submit button asking for no loader was rendered with the loader class.',
    );
  }

  /**
   * Reads an element type's pre-render callbacks out of the real registry.
   *
   * @param string $type
   *   The element type to look up.
   *
   * @return array
   *   Its `#pre_render` callbacks, in the order the pipeline will run them.
   */
  private function preRender(string $type): array {
    return $this->container->get('plugin.manager.element_info')
      ->getInfoProperty($type, '#pre_render', []);
  }

}
