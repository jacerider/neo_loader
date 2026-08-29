<?php

declare(strict_types=1);

namespace Drupal\Tests\neo_loader\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\neo_loader\Plugin\LoaderPluginBase;
use Drupal\neo_loader\Plugin\LoaderPluginInterface;
use PHPUnit\Framework\Attributes\Group;

/**
 * Covers the retirement of the loader plugin type's dead label accessor.
 *
 * `getLabel()` returned `$this->configuration['label']`, and a loader is only
 * ever built with no configuration array, so the method would have raised an
 * undefined-array-key error the first time anybody called it. Nobody ever did:
 * the label the module uses is read straight off the definition. An absent
 * method has nothing to exercise, so the shape of the type is what is asserted
 * — by reflection, without a container, which is what makes this a unit test.
 */
#[Group('neo_loader')]
final class LoaderLabelAccessorTest extends UnitTestCase {

  /**
   * Tests that the loader plugin interface exposes no `getLabel()`.
   */
  public function testExposesNoGetLabelOnTheLoaderPluginInterface(): void {
    $methods = array_map(
      static fn(\ReflectionMethod $method): string => $method->getName(),
      (new \ReflectionClass(LoaderPluginInterface::class))->getMethods(),
    );

    $this->assertNotContains(
      'getLabel',
      $methods,
      'The loader plugin interface still advertises getLabel().',
    );
  }

  /**
   * Tests that the base class exposes neither getLabel() nor $label.
   */
  public function testExposesNoGetLabelAndNoLabelPropertyOnTheLoaderPluginBase(): void {
    $base = new \ReflectionClass(LoaderPluginBase::class);

    $methods = array_map(
      static fn(\ReflectionMethod $method): string => $method->getName(),
      $base->getMethods(),
    );
    $this->assertNotContains(
      'getLabel',
      $methods,
      'The loader plugin base class still advertises getLabel().',
    );

    $properties = array_map(
      static fn(\ReflectionProperty $property): string => $property->getName(),
      $base->getProperties(),
    );
    $this->assertNotContains(
      'label',
      $properties,
      'The loader plugin base class still carries the unused $label property.',
    );
  }

}
