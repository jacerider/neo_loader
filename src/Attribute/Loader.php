<?php

declare(strict_types=1);

namespace Drupal\neo_loader\Attribute;

use Drupal\Component\Plugin\Attribute\AttributeBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * The neo_loader attribute.
 *
 * This is the spelling a loader plugin should be written in. The `@Loader`
 * annotation beside it is the legacy spelling and is not deprecated: the
 * attribute is new in this release, and a site with its own loader should get
 * one released version in which both spellings are simply fine.
 *
 * @see \Drupal\neo_loader\Annotation\Loader
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class Loader extends AttributeBase {

  /**
   * Constructs a new Loader instance.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $label
   *   (optional) The human-readable name of the loader.
   */
  public function __construct(
    public readonly string $id,
    public readonly ?TranslatableMarkup $label = NULL,
  ) {}

}
