<?php

declare(strict_types=1);

namespace Drupal\Tests\neo_loader\Unit;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\neo_loader\LoaderManagerInterface;
use Drupal\neo_loader\Settings\LoaderSettings;
use Symfony\Component\Routing\Route;

/**
 * Builds the loader settings plugin without a container.
 *
 * The plugin is constructible with no container at all: the settings base
 * class's constructor only rearranges the configuration arrays it is handed,
 * the values trait is plain array access, and both remaining collaborators are
 * mockable. That is what lets every unit test in this plan hand the plugin a
 * values array and a real admin context instead of booting a kernel.
 *
 * The admin context is built for real rather than mocked, because the answer
 * this plan cares about — "is there an admin route?" — is core's own, and a
 * double of it would only restate the expectation back to the test.
 */
trait LoaderSettingsConstructionTrait {

  /**
   * Builds a loader settings plugin from doubles and a values array.
   *
   * @param array $values
   *   The settings values the plugin reads through getValue().
   * @param \Drupal\Core\Routing\AdminContext|null $admin_context
   *   The admin context, or NULL for one that sees no route object at all.
   * @param \Drupal\neo_loader\LoaderManagerInterface|null $loader_manager
   *   The loader manager, or NULL for a double with no expectations.
   *
   * @return \Drupal\neo_loader\Settings\LoaderSettings
   *   The constructed plugin.
   */
  protected function buildLoaderSettings(
    array $values = [],
    ?AdminContext $admin_context = NULL,
    ?LoaderManagerInterface $loader_manager = NULL,
  ): LoaderSettings {
    return new LoaderSettings(
      [
        'config' => $values,
        'variation' => [],
        'variation_id' => '',
      ],
      'neo_loader',
      ['configuration' => []],
      $this->createMock(MessengerInterface::class),
      $this->createMock(FormBuilderInterface::class),
      $loader_manager ?? $this->createMock(LoaderManagerInterface::class),
      $admin_context ?? $this->adminContextForRoute(NULL),
    );
  }

  /**
   * Builds a real admin context over a route match answering with one route.
   *
   * @param \Symfony\Component\Routing\Route|null $route
   *   The route the match answers with, or NULL for no route object at all.
   * @param \PHPUnit\Framework\MockObject\MockObject|null $route_match
   *   A route match double to use instead of a fresh one, so that a test can
   *   carry its own expectations about what the context is asked.
   *
   * @return \Drupal\Core\Routing\AdminContext
   *   The admin context.
   */
  protected function adminContextForRoute(?Route $route, $route_match = NULL): AdminContext {
    $route_match = $route_match ?? $this->createMock(RouteMatchInterface::class);
    $route_match->method('getRouteObject')->willReturn($route);
    return new AdminContext($route_match);
  }

  /**
   * Builds a route flagged as an admin route.
   *
   * @return \Symfony\Component\Routing\Route
   *   The route.
   */
  protected function adminRoute(): Route {
    return new Route('/admin/config/neo/neo-loader', [], [], ['_admin_route' => TRUE]);
  }

  /**
   * Builds a route that is not an admin route.
   *
   * @return \Symfony\Component\Routing\Route
   *   The route.
   */
  protected function frontEndRoute(): Route {
    return new Route('/node/1');
  }

}
