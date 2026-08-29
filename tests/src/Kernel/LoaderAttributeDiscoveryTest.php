<?php

declare(strict_types=1);

namespace Drupal\Tests\neo_loader\Kernel;

use Drupal\Core\Render\RenderContext;
use Drupal\neo_loader\Attribute\Loader as LoaderAttribute;
use Drupal\neo_loader\Plugin\Loader\LoaderIcon;
use PHPUnit\Framework\Attributes\Group;

/**
 * Covers the loader plugin type being declared with an attribute.
 *
 * The fixtures live in `neo_loader_test` rather than in `neo_loader` itself,
 * so that both spellings are proved from where a site would actually write
 * one — another extension — and so that the twelve shipped definitions the
 * characterisation suite pins are left at twelve.
 */
#[Group('neo_loader')]
final class LoaderAttributeDiscoveryTest extends LoaderKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['neo_loader_test'];

  /**
   * Tests that a loader declared with the `#[Loader]` attribute is discovered.
   */
  public function testDiscoversLoaderDeclaredWithTheAttribute(): void {
    $definitions = $this->loaderManager()->getDefinitions();

    $this->assertArrayHasKey('neo_loader_test_attribute', $definitions);

    $definition = $definitions['neo_loader_test_attribute'];
    $this->assertSame('neo_loader_test_attribute', $definition['id']);
    $this->assertSame('Test attribute', (string) $definition['label']);
    $this->assertSame('neo_loader_test', $definition['provider']);
    $this->assertSame(
      'Drupal\neo_loader_test\Plugin\Loader\LoaderTestAttribute',
      $definition['class'],
    );
    $this->assertSame(
      '<div class="neo-loader-test-attribute"></div>',
      $this->loaderManager()->createInstance('neo_loader_test_attribute')->getMarkup(),
    );
  }

  /**
   * Tests that a loader declared with the `@Loader` annotation still resolves.
   */
  public function testStillDiscoversLoaderDeclaredWithTheAnnotation(): void {
    $definitions = $this->loaderManager()->getDefinitions();

    $this->assertArrayHasKey('neo_loader_test_annotation', $definitions);

    $definition = $definitions['neo_loader_test_annotation'];
    $this->assertSame('neo_loader_test_annotation', $definition['id']);
    $this->assertSame('Test annotation', (string) $definition['label']);
    $this->assertSame('neo_loader_test', $definition['provider']);
    $this->assertSame(
      'Drupal\neo_loader_test\Plugin\Loader\LoaderTestAnnotation',
      $definition['class'],
    );
    $this->assertSame(
      '<div class="neo-loader-test-annotation"></div>',
      $this->loaderManager()->createInstance('neo_loader_test_annotation')->getMarkup(),
    );

    // Both spellings come back from one definition set. Asserting the pair
    // rather than the annotation alone is the point: the annotation "still"
    // resolving means nothing unless it resolves beside an attribute.
    $fixtures = array_intersect_key($definitions, array_flip([
      'neo_loader_test_annotation',
      'neo_loader_test_attribute',
    ]));
    $this->assertSame(
      ['neo_loader_test_annotation', 'neo_loader_test_attribute'],
      array_keys($fixtures),
    );
  }

  /**
   * Tests the icon loader resolving from its attribute, unchanged.
   */
  public function testResolvesTheIconLoaderFromItsAttribute(): void {
    $reflection = new \ReflectionClass(LoaderIcon::class);
    $attributes = $reflection->getAttributes(LoaderAttribute::class);

    $this->assertCount(1, $attributes);
    $attribute = $attributes[0]->newInstance();
    $this->assertSame('icon', $attribute->getId());
    $this->assertSame('Icon', (string) $attribute->label);
    // Converted, not doubled up: a class carrying both spellings would let
    // the attribute be wrong without anything noticing.
    $this->assertStringNotContainsString('@Loader', (string) $reflection->getDocComment());

    $definition = $this->loaderManager()->getDefinition('icon');
    $this->assertSame('icon', $definition['id']);
    $this->assertSame('Icon', (string) $definition['label']);
    $this->assertSame('neo_loader', $definition['provider']);
    $this->assertSame(LoaderIcon::class, $definition['class']);

    // The markup is `neo_icon`'s answer wrapped by the loader, so it is
    // rendered inside a context exactly as the theme hook renders it.
    $markup = NULL;
    $manager = $this->loaderManager();
    $this->container->get('renderer')->executeInRenderContext(
      new RenderContext(),
      static function () use (&$markup, $manager): void {
        $markup = $manager->createInstance('icon')->getMarkup();
      },
    );
    $this->assertSame('<div class="animate-spin">Loading...</div>', $markup);
  }

}
