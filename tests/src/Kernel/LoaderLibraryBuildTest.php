<?php

declare(strict_types=1);

namespace Drupal\Tests\neo_loader\Kernel;

use PHPUnit\Framework\Attributes\Group;

/**
 * Pins the loader libraries `hook_library_info_build()` generates today.
 *
 * Library names and stylesheet paths are what a site's cached CSS aggregate is
 * keyed on, so this is the seam where an invisible change stops being
 * invisible. The whole generated set is asserted, not just the names.
 */
#[Group('neo_loader')]
final class LoaderLibraryBuildTest extends LoaderKernelTestBase {

  /**
   * Tests the twelve built libraries' names and stylesheet paths.
   */
  public function testBuildsTwelveLoaderLibrariesWithShippedNamesAndPaths(): void {
    $this->assertSame(
      $this->expectedLibraries(),
      neo_loader_library_info_build(),
    );
  }

  /**
   * The twelve library definitions, spelled out rather than derived.
   *
   * Derived expectations would restate the rule under test and pass whatever
   * the rule became. The ids' underscores are written as dashes twice over —
   * once in the library name, once in the stylesheet filename — and both are
   * written out here so that either drifting is a failure.
   *
   * @return array<string, array<string, mixed>>
   *   The libraries keyed by name, in discovery order.
   */
  private function expectedLibraries(): array {
    $names = [
      'chasing-dots',
      'circle',
      'cube-grid',
      'double-bounce',
      'fading-circle',
      'folding-cube',
      'icon',
      'pulse',
      'rotating-plane',
      'three-bounce',
      'wandering-cubes',
      'wave',
    ];
    $libraries = [];
    foreach ($names as $name) {
      $libraries['plugin.' . $name] = [
        'neo' => [
          'group' => 'contrib',
        ],
        'css' => [
          'base' => [
            'src/css/loader/' . $name . '.css' => [],
          ],
        ],
      ];
    }
    return $libraries;
  }

}
