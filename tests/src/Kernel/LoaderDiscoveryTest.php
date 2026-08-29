<?php

declare(strict_types=1);

namespace Drupal\Tests\neo_loader\Kernel;

use Drupal\neo_loader\Plugin\LoaderPluginInterface;
use PHPUnit\Framework\Attributes\Group;

/**
 * Pins what loader discovery answers today.
 *
 * The manager is about to be rewritten to read declarations as well as
 * classes. These tests are written before that happens and assert the answers
 * the twelve shipped class plugins produce now, so that the rewrite can be
 * shown to change nothing a site can see.
 */
#[Group('neo_loader')]
final class LoaderDiscoveryTest extends LoaderKernelTestBase {

  /**
   * Tests that it discovers twelve definitions with the shipped ids and labels.
   */
  public function testDiscoversTwelveDefinitionsWithShippedIdsAndLabels(): void {
    $labels = [];
    foreach ($this->loaderManager()->getDefinitions() as $id => $definition) {
      $labels[(string) $id] = (string) $definition['label'];
    }
    ksort($labels);

    $this->assertSame([
      'chasing_dots' => 'Chasing dots',
      'circle' => 'Circle',
      // Misspelled in the shipped plugin, and pinned as shipped. Fixing it
      // changes what the settings form shows, so it belongs to a ticket that
      // says so rather than to a plan whose whole value is that nothing moves.
      'cube_grid' => 'Cube gird',
      'double_bounce' => 'Double bounce',
      'fading_circle' => 'Fading circle',
      'folding_cube' => 'Folding cube',
      'icon' => 'Icon',
      'pulse' => 'Pulse',
      'rotating_plane' => 'Rotating plane',
      'three_bounce' => 'Three bounce',
      'wandering_cubes' => 'Wandering cubes',
      'wave' => 'Wave',
    ], $labels);
  }

  /**
   * Tests the natural-sorted option list and the twelve keyed instances.
   */
  public function testReturnsNaturallySortedOptionsAndTwelveKeyedInstances(): void {
    $options = array_map(
      static fn ($label): string => (string) $label,
      $this->loaderManager()->getLoaderOptionList(),
    );

    $this->assertSame([
      'chasing_dots' => 'Chasing dots',
      'circle' => 'Circle',
      // Misspelled in the shipped plugin, and pinned as shipped. Fixing it
      // changes what the settings form shows, so it belongs to a ticket that
      // says so rather than to a plan whose whole value is that nothing moves.
      'cube_grid' => 'Cube gird',
      'double_bounce' => 'Double bounce',
      'fading_circle' => 'Fading circle',
      'folding_cube' => 'Folding cube',
      'icon' => 'Icon',
      'pulse' => 'Pulse',
      'rotating_plane' => 'Rotating plane',
      'three_bounce' => 'Three bounce',
      'wandering_cubes' => 'Wandering cubes',
      'wave' => 'Wave',
    ], $options);

    $instances = $this->loaderManager()->createInstances();
    $this->assertSame(array_keys($options), array_keys($instances));
    foreach ($instances as $id => $instance) {
      $this->assertInstanceOf(LoaderPluginInterface::class, $instance);
      $this->assertSame($id, $instance->getPluginId());
    }
  }

}
