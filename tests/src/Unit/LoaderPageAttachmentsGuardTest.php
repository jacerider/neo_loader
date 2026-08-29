<?php

declare(strict_types=1);

namespace Drupal\Tests\neo_loader\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Render\RendererInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\neo_loader\Settings\LoaderSettings;
use Drupal\neo_settings\Plugin\SettingsInterface;
use Drupal\neo_settings\SettingsRepositoryInterface;
use PHPUnit\Framework\Attributes\Group;

/**
 * Covers what the page-attachment hook is entitled to ask of the settings.
 *
 * The hook asks the settings repository for the active settings and then calls
 * route applicability on the answer. The repository's contract promises only
 * the settings interface, which declares no such method — it belongs to the
 * loader settings plugin alone. The call has always worked because the
 * concrete plugin is what comes back, and nothing checked it, because the
 * docblock that was supposed to assert it named a class that does not exist.
 *
 * Nothing here changes at runtime on a site: a plugin that disallows
 * variations always answers with its core instance, so the guard always holds.
 * What the guard buys is that the assumption is now checkable — by a reader,
 * by an IDE and by a static analyser — which is exactly what the second case
 * below pins.
 *
 * The first case builds a real plugin rather than a double, because the class
 * is final; that is the construction helper this plan's other unit tests share.
 *
 * @see neo_loader_page_attachments()
 */
#[Group('neo_loader')]
final class LoaderPageAttachmentsGuardTest extends UnitTestCase {

  use LoaderSettingsConstructionTrait;

  /**
   * The markup the renderer double answers the hook's render call with.
   */
  private const MARKUP = '<div class="neo-loader"></div>';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    require_once __DIR__ . '/../../../neo_loader.module';
  }

  /**
   * Tests that the loader library and its payload attach for loader settings.
   */
  public function testAttachesTheLoaderLibraryAndItsPayloadForTheLoaderSettings(): void {
    $this->setActiveSettings($this->buildLoaderSettings(
      [
        'loader' => 'wave',
        'hide_ajax_message' => TRUE,
        'always_fullscreen' => FALSE,
        'loader_position' => 'center',
        'show_admin_paths' => FALSE,
      ],
      $this->adminContextForRoute($this->frontEndRoute()),
    ));

    $page = [];
    neo_loader_page_attachments($page);

    $this->assertSame(
      ['neo_loader/loader'],
      $page['#attached']['library'] ?? [],
      'The hook did not attach the loader library.',
    );
    $this->assertSame(
      [
        'markup' => self::MARKUP,
        'hideAjaxMessage' => TRUE,
        'alwaysFullscreen' => FALSE,
        'loaderPosition' => 'center',
      ],
      $page['#attached']['drupalSettings']['neoLoader'] ?? [],
      'The hook did not attach the loader settings payload.',
    );
  }

  /**
   * Tests that some other settings plugin attaches nothing and raises nothing.
   */
  public function testAttachesNothingAndRaisesNothingForSomeOtherSettingsPlugin(): void {
    $other = $this->createMock(SettingsInterface::class);
    $other->method('getValue')->willReturn('wave');
    $this->setActiveSettings($other);

    $page = [];
    neo_loader_page_attachments($page);

    $this->assertSame(
      [],
      $page,
      'The hook attached something for settings that are not the loader plugin.',
    );
  }

  /**
   * Tests that nothing attaches when no loader is named in the settings.
   */
  public function testAttachesNothingWhenNoLoaderIsNamedInTheSettings(): void {
    $this->setActiveSettings($this->buildLoaderSettings(
      ['loader' => '', 'show_admin_paths' => TRUE],
      $this->adminContextForRoute($this->frontEndRoute()),
    ));

    $page = [];
    neo_loader_page_attachments($page);

    $this->assertSame(
      [],
      $page,
      'The hook attached the loader with no loader named in the settings.',
    );
  }

  /**
   * Tests that nothing attaches when route applicability answers no.
   */
  public function testAttachesNothingWhenRouteApplicabilityAnswersNo(): void {
    $this->setActiveSettings($this->buildLoaderSettings(
      ['loader' => 'wave', 'show_admin_paths' => FALSE],
      $this->adminContextForRoute($this->adminRoute()),
    ));

    $page = [];
    neo_loader_page_attachments($page);

    $this->assertSame(
      [],
      $page,
      'The hook attached the loader on a route it is not applicable to.',
    );
  }

  /**
   * Tests that every docblock annotating the active settings names a class.
   */
  public function testEveryActiveSettingsDocblockNamesTheClassThatExists(): void {
    $source = (string) file_get_contents(__DIR__ . '/../../../neo_loader.module');
    preg_match_all(
      '#@var \\\\(Drupal\\\\neo_loader\\\\Settings\\\\\w+)#',
      $source,
      $matches,
    );
    $this->assertCount(
      2,
      $matches[1],
      'The module file no longer annotates the active settings in two places.',
    );
    foreach ($matches[1] as $class) {
      $this->assertTrue(
        class_exists('\\' . $class),
        'The module file annotates the active settings with a class that does not exist: ' . $class,
      );
      $this->assertSame(
        ltrim(LoaderSettings::class, '\\'),
        $class,
        'The module file annotates the active settings with the wrong class.',
      );
    }
  }

  /**
   * Puts a container holding the hook's collaborators in place.
   *
   * @param \Drupal\neo_settings\Plugin\SettingsInterface $settings
   *   The settings the repository answers with.
   */
  private function setActiveSettings(SettingsInterface $settings): void {
    $repository = $this->createMock(SettingsRepositoryInterface::class);
    $repository->method('getActive')->willReturn($settings);
    $renderer = $this->createMock(RendererInterface::class);
    $renderer->method('render')->willReturn(self::MARKUP);

    $container = new ContainerBuilder();
    $container->set('neo_loader.settings', $repository);
    $container->set('renderer', $renderer);
    \Drupal::setContainer($container);
  }

}
