<?php

declare(strict_types=1);

namespace Drupal\Tests\neo_loader\Unit;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\neo_loader\LoaderManager;
use PHPUnit\Framework\Attributes\Group;

/**
 * Covers what the loader manager declares to core's plugin manager.
 *
 * Construction is genuinely pure — it reads no container, touches no
 * filesystem and resolves no plugin — so the criterion that the manager stops
 * raising core's annotation-only deprecation is assertable without a kernel.
 *
 * The deprecation core raises here is `@`-suppressed, which hides it from the
 * default handler but not from a handler installed for the call: PHP invokes
 * an error handler for a suppressed diagnostic all the same. That is why this
 * test installs its own rather than reading anything PHPUnit collected.
 */
#[Group('neo_loader')]
final class LoaderManagerDiscoveryDeclarationTest extends UnitTestCase {

  /**
   * Tests that constructing the manager raises no discovery deprecation.
   */
  public function testConstructsWithoutTheAnnotationOnlyDiscoveryDeprecation(): void {
    $deprecations = [];
    set_error_handler(
      static function (int $errno, string $message) use (&$deprecations): bool {
        $deprecations[] = $message;
        return TRUE;
      },
      E_USER_DEPRECATED,
    );
    try {
      new LoaderManager(
        new \ArrayObject([]),
        $this->createMock(CacheBackendInterface::class),
        $this->createMock(ModuleHandlerInterface::class),
      );
    }
    finally {
      restore_error_handler();
    }

    $this->assertSame([], $deprecations);
  }

}
