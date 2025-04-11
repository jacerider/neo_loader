<?php

namespace Drupal\neo_loader;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Defines Twig extensions.
 */
class TwigExtension extends AbstractExtension {

  /**
   * Gets a unique identifier for this Twig extension.
   *
   * @return string
   *   A unique identifier for this Twig extension.
   */
  public function getName() {
    return 'twig.neo_loader';
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new TwigFunction('neo_loader', [$this, 'renderLoader']),
    ];
  }

  /**
   * Render the neo image style.
   *
   * @param string $title
   *   The loader title.
   * @param string $color
   *   The loader color. Example: primary-500.
   * @param string $loader
   *   The loader style. Default: 'wave'.
   * @param string $ajax
   *   The URL to load when the loader comes into view. Default: 'false'.
   */
  public static function renderLoader(string $title = NULL, string $color = NULL, string $loader = NULL, string $ajax = NULL) {
    $build = [
      '#theme' => 'neo_loader',
      '#title' => $title,
      '#color' => $color,
      '#loader' => $loader,
      '#ajax' => $ajax,
    ];
    return $build;
  }

}
