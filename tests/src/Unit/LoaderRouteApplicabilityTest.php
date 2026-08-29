<?php

declare(strict_types=1);

namespace Drupal\Tests\neo_loader\Unit;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\neo_loader\Settings\LoaderSettings;
use PHPUnit\Framework\Attributes\Group;

/**
 * Covers route applicability — the question the page-attachment path asks.
 *
 * The matrix the method always meant is small: not an admin route, applicable;
 * an admin route, applicable exactly when the admin-paths setting is on. With
 * no route object at all the admin context answers "not an admin route", so
 * the answer is "applicable" and needs no added guard.
 *
 * The method used to carry a third arm comparing the current route name
 * against `neo.loader`, a route no module or theme declares. It never fired,
 * and the settings page grew a control — the loader test — that depends on it
 * never firing. Repairing it to name the route that exists is the obvious
 * reading and is the failure; see ADR 0002. The last test here pins that the
 * comparison, and the request read that fed it, stay gone.
 */
#[Group('neo_loader')]
final class LoaderRouteApplicabilityTest extends UnitTestCase {

  use LoaderSettingsConstructionTrait;

  /**
   * Tests that it is applicable on a route that is not an admin route.
   */
  public function testIsApplicableOnRouteThatIsNotAnAdminRoute(): void {
    $settings = $this->buildLoaderSettings(
      ['show_admin_paths' => FALSE],
      $this->adminContextForRoute($this->frontEndRoute()),
    );

    $this->assertTrue(
      $settings->routeIsApplicable(),
      'The loader is not applicable on a front-end route.',
    );
  }

  /**
   * Tests that it is applicable on an admin route with the setting on.
   */
  public function testIsApplicableOnAnAdminRouteWhenTheAdminPathsSettingIsOn(): void {
    $settings = $this->buildLoaderSettings(
      ['show_admin_paths' => TRUE],
      $this->adminContextForRoute($this->adminRoute()),
    );

    $this->assertTrue(
      $settings->routeIsApplicable(),
      'The loader is not applicable on an admin route with admin paths on.',
    );
  }

  /**
   * Tests that it is not applicable on an admin route with the setting off.
   */
  public function testIsNotApplicableOnAnAdminRouteWhenTheAdminPathsSettingIsOff(): void {
    $settings = $this->buildLoaderSettings(
      ['show_admin_paths' => FALSE],
      $this->adminContextForRoute($this->adminRoute()),
    );

    $this->assertFalse(
      $settings->routeIsApplicable(),
      'The loader is applicable on an admin route with admin paths off.',
    );
  }

  /**
   * Tests that it is applicable when there is no route object at all.
   */
  public function testIsApplicableWhenThereIsNoRouteObjectAtAll(): void {
    $settings = $this->buildLoaderSettings(
      ['show_admin_paths' => FALSE],
      $this->adminContextForRoute(NULL),
    );

    $this->assertTrue(
      $settings->routeIsApplicable(),
      'The loader is not applicable with no route object at all.',
    );
  }

  /**
   * Tests that it reaches every answer without consulting a route name.
   */
  public function testReachesEveryAnswerWithoutConsultingRouteName(): void {
    foreach ([
      [$this->adminRoute(), TRUE, TRUE],
      [$this->adminRoute(), FALSE, FALSE],
      [$this->frontEndRoute(), FALSE, TRUE],
      [NULL, FALSE, TRUE],
    ] as [$route, $show_admin_paths, $expected]) {
      $route_match = $this->createMock(RouteMatchInterface::class);
      $route_match->expects($this->never())->method('getRouteName');
      $settings = $this->buildLoaderSettings(
        ['show_admin_paths' => $show_admin_paths],
        $this->adminContextForRoute($route, $route_match),
      );

      $this->assertSame(
        $expected,
        $settings->routeIsApplicable(),
        'Route applicability answered the wrong way round.',
      );
    }

    $source = $this->routeIsApplicableSource();
    $this->assertStringNotContainsString(
      'neo.loader',
      $source,
      'Route applicability still compares against a route that does not exist.',
    );
    $this->assertStringNotContainsString(
      '_route',
      $source,
      'Route applicability still reads the current route name off the request.',
    );
    $this->assertStringNotContainsString(
      'getRouteName',
      $source,
      'Route applicability still asks for the current route name.',
    );
  }

  /**
   * Tests that the method states what it answers rather than inheriting it.
   */
  public function testStatesWhatItAnswersInsteadOfInheritingNothing(): void {
    $docblock = (string) (new \ReflectionMethod(
      LoaderSettings::class,
      'routeIsApplicable',
    ))->getDocComment();

    $this->assertStringNotContainsString(
      '{@inheritdoc}',
      $docblock,
      'Route applicability claims to inherit documentation it has no parent for.',
    );
    $this->assertStringContainsString(
      '@return bool',
      $docblock,
      'Route applicability does not declare what it returns.',
    );
  }

  /**
   * Returns the source of route applicability, as written in the file.
   *
   * @return string
   *   The lines of the method.
   */
  private function routeIsApplicableSource(): string {
    $method = new \ReflectionMethod(
      LoaderSettings::class,
      'routeIsApplicable',
    );
    $lines = (array) file((string) $method->getFileName());

    return implode('', array_slice(
      $lines,
      $method->getStartLine() - 1,
      $method->getEndLine() - $method->getStartLine() + 1,
    ));
  }

}
