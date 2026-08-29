<?php

declare(strict_types=1);

namespace Drupal\Tests\neo_loader\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\neo_loader\NeoLoaderPreRender;
use PHPUnit\Framework\Attributes\Group;

/**
 * Pins what the element registry holds when the modal module is not installed.
 *
 * The same arrangement as the sibling registry test with one module removed,
 * which is the only way to observe this branch: `neo_loader.info.yml` depends
 * on `neo` alone, so a site can perfectly well run the loader without the
 * modal module, and on such a site the hook writes a pre-render onto a name
 * nothing owns.
 *
 * The fabricated entry is a defect this plan pins rather than fixes. It is
 * inert today — nothing renders an element type that no plugin declares — so
 * the point of the assertion is that the next reader meets the fabrication as
 * a recorded fact rather than discovering it.
 *
 * @see \Drupal\Tests\neo_loader\Kernel\LoaderElementRegistryTest
 * @see \Drupal\Tests\neo_loader\Unit\LoaderElementInfoAlterTest
 */
#[Group('neo_loader')]
final class LoaderFabricatedModalElementTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   *
   * Deliberately without `neo_modal`, which is what makes the modal link a
   * name rather than an element type.
   */
  protected static $modules = [
    'system',
    'neo_settings',
    'neo_icon',
    'neo_loader',
  ];

  /**
   * Tests the fabricated modal link entry real element info comes back with.
   */
  public function testReturnsTheFabricatedModalLinkEntryFromTheRealElementInfo(): void {
    $elementInfo = $this->container->get('plugin.manager.element_info');

    // No plugin declares the type, so nothing built it.
    $this->assertArrayNotHasKey(
      'neo_modal_link',
      $elementInfo->getDefinitions(),
      'The modal link element type is declared by a plugin, so this test no longer observes the fabrication.',
    );

    // Yet element info answers for it, because the hook wrote a pre-render
    // onto the name. `#defaults_loaded` is the registry's own doing, added to
    // whatever it returns; everything else here is the module's.
    $this->assertSame(
      [
        '#pre_render' => [[NeoLoaderPreRender::class, 'loader']],
        '#defaults_loaded' => TRUE,
      ],
      $elementInfo->getInfo('neo_modal_link'),
      'The element registry no longer carries a fabricated modal link entry.',
    );

    // What the fabrication is missing is the point of calling it one: no
    // theme, no class, no attributes, and not even the `#type` key every real
    // element type gets — that is written before the alter runs, so an entry
    // the alter creates never receives it.
    $this->assertArrayNotHasKey(
      '#type',
      $elementInfo->getInfo('neo_modal_link'),
      'The fabricated modal link entry carries a type key, so it was built rather than fabricated.',
    );

    // An element type that does not exist and is not named is simply absent,
    // which is what the fabricated entry would look like if the hook checked.
    $this->assertSame(
      ['#defaults_loaded' => TRUE],
      $elementInfo->getInfo('neo_loader_no_such_element'),
      'The element registry answered for a type nothing declares and nothing names.',
    );
  }

}
