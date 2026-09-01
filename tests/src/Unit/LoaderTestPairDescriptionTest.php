<?php

declare(strict_types=1);

namespace Drupal\Tests\neo_loader\Unit;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Tests\UnitTestCase;
use Drupal\neo_loader\LoaderManagerInterface;
use PHPUnit\Framework\Attributes\Group;

/**
 * Covers the description the pair of loader test controls carries.
 *
 * Both controls reach their presentation whatever the always_fullscreen
 * setting says, which is the point of the pair -- and is exactly what leaves
 * the setting's own effect unstated. Two equal buttons say nothing about which
 * presentation every other ajax request on the site actually gets, and a
 * builder reading the form has no way to tell which of the two tests is the
 * live one and which is the preview of the road not taken.
 *
 * So the pair carries one description naming the presentation the current
 * value produces. It is asserted for both values of the setting rather than
 * for the shipped default alone, because a description that names one
 * presentation unconditionally is right half the time and reads as authority
 * the whole time.
 *
 * The discriminator is the presentation named, not the sentence: what the
 * criterion asks is that the description follows the setting, and pinning the
 * prose here as well would only duplicate LoaderSettingsFormStringsTest, which
 * owns the wording and the translation seam.
 *
 * The seam is the sibling tests': the plugin is constructible from doubles
 * with no container, and the protected form builder is reached by reflection.
 */
#[Group('neo_loader')]
final class LoaderTestPairDescriptionTest extends UnitTestCase {

  use LoaderSettingsConstructionTrait;

  /**
   * Tests that it names the presentation the current setting produces.
   */
  public function testDescribesThePairWithThePresentationTheSettingProduces(): void {
    $cases = [
      'on' => [TRUE, 'fullscreen overlay', 'inline throbber'],
      'off' => [FALSE, 'inline throbber', 'fullscreen overlay'],
    ];

    foreach ($cases as $state => [$always_fullscreen, $live, $other]) {
      $description = $this->buildSettingsForm($always_fullscreen)['test']['#description'] ?? NULL;

      $this->assertInstanceOf(
        TranslatableMarkup::class,
        $description,
        'With the overlay setting ' . $state . ' the loader test pair carries '
        . 'no description, so the form does not say which of the two tests is '
        . 'the live one.',
      );
      $this->assertStringContainsString(
        $live,
        (string) $description,
        'With the overlay setting ' . $state . ' the description does not name '
        . 'the ' . $live . ', which is what ajax requests actually produce.',
      );
      $this->assertStringNotContainsString(
        $other,
        (string) $description,
        'With the overlay setting ' . $state . ' the description names the '
        . $other . ', which is the presentation the setting does not produce.',
      );
    }
  }

  /**
   * Tests that it describes the pair rather than either control.
   *
   * The claim is about the setting, not about a button: put on one control it
   * reads as that control's own description, and put on both it says the same
   * thing twice under two different buttons.
   */
  public function testDescribesThePairRatherThanEitherControl(): void {
    $pair = $this->buildSettingsForm(TRUE)['test'];

    foreach (['fullscreen', 'throbber'] as $presentation) {
      $this->assertArrayNotHasKey(
        '#description',
        $pair[$presentation] ?? [],
        'The ' . $presentation . ' control carries a description of its own, '
        . 'so the pair is described twice.',
      );
    }
  }

  /**
   * Builds the settings form through the plugin's protected form builder.
   *
   * @param bool $always_fullscreen
   *   The value of the always-fullscreen setting the form is built against.
   *
   * @return array
   *   The built form.
   */
  private function buildSettingsForm(bool $always_fullscreen): array {
    $loader_manager = $this->createMock(LoaderManagerInterface::class);
    $loader_manager->method('getLoaderOptionList')->willReturn([
      'circle' => 'Circle',
    ]);

    $settings = $this->buildLoaderSettings([
      'loader' => 'circle',
      'color' => 'base-content',
      'hide_ajax_message' => FALSE,
      'always_fullscreen' => $always_fullscreen,
      'show_admin_paths' => TRUE,
      'loader_position' => 'body',
    ], NULL, $loader_manager);
    $settings->setStringTranslation($this->getStringTranslationStub());

    $build = new \ReflectionMethod($settings, 'buildForm');

    return $build->invoke(
      $settings,
      ['#parents' => []],
      $this->createMock(FormStateInterface::class),
    );
  }

}
