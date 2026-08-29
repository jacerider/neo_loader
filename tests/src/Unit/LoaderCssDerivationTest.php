<?php

declare(strict_types=1);

namespace Drupal\Tests\neo_loader\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\neo_loader\LoaderManager;
use PHPUnit\Framework\Attributes\Group;

/**
 * Covers the rule that turns a loader id into a stylesheet path.
 *
 * The derivation is the whole reason twelve classes could stop carrying a
 * filename, and it is genuinely pure: an id in, a path out, no container, no
 * filesystem and no plugin. It gets a unit test so that the rule has a fast
 * test naming it, rather than only being observed through a library build.
 */
#[Group('neo_loader')]
final class LoaderCssDerivationTest extends UnitTestCase {

  /**
   * Tests deriving a loader's stylesheet path from its id.
   */
  public function testDerivesTheStylesheetPathFromTheLoaderId(): void {
    // The id is written with dashes in the filename and nowhere else, which is
    // the half of the rule a hand-written path kept getting to disagree with.
    $this->assertSame(
      'src/css/loader/wave.css',
      LoaderManager::deriveCssFile('wave'),
    );
    $this->assertSame(
      'src/css/loader/cube-grid.css',
      LoaderManager::deriveCssFile('cube_grid'),
    );
    $this->assertSame(
      'src/css/loader/neo-loader-test-declaration.css',
      LoaderManager::deriveCssFile('neo_loader_test_declaration'),
    );
  }

}
