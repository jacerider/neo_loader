<?php

namespace Drupal\neo_loader\Plugin;

use Drupal\Core\Plugin\PluginBase;

/**
 * Class LoaderBase.
 */
abstract class LoaderPluginBase extends PluginBase implements LoaderPluginInterface {

  /**
   * The path to neo.
   *
   * Assigned the literal 'src' in the constructor and never varied. It exists
   * so that a `setCssFile()` body written before the manager owned the
   * stylesheet question keeps resolving.
   *
   * @var string
   *
   * @deprecated in neo_loader:1.1.0 and is removed from neo_loader:2.0.0. The
   *   manager answers a loader's stylesheet from its definition, so nothing
   *   needs to build the path by hand.
   *
   * @phpcs:ignore Drupal.Commenting.Deprecated.DeprecatedWrongSeeUrlFormat
   * @see \Drupal\neo_loader\LoaderManagerInterface::getCssFile()
   */
  protected $path;

  /**
   * The markup for the loader.
   *
   * @var mixed
   */
  protected $markup;

  /**
   * The CSS file path.
   *
   * @var string
   */
  protected $cssFile;

  /**
   * LoaderPluginBase constructor.
   *
   * @param array $configuration
   *   Array with configuration.
   * @param string $plugin_id
   *   String with plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition value.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    // The stylesheet half of a loader is deprecated, not removed, so the
    // constructor keeps filling it in: a loader written against it has to go
    // on working for the release that deprecates it.
    // @phpstan-ignore property.deprecated
    $this->path = 'src';
    $this->markup = $this->setMarkup();
    // @phpstan-ignore method.deprecated
    $this->cssFile = $this->setCssFile();
  }

  /**
   * Function to get markup.
   *
   * @return mixed
   *   Return markup.
   */
  public function getMarkup() {
    return $this->markup;
  }

  /**
   * Function to get css file.
   *
   * @return mixed
   *   Return the css file.
   *
   * @deprecated in neo_loader:1.1.0 and is removed from neo_loader:2.0.0. Use
   *   \Drupal\neo_loader\LoaderManagerInterface::getCssFile() instead.
   *
   * @phpcs:ignore Drupal.Commenting.Deprecated.DeprecatedWrongSeeUrlFormat
   * @see \Drupal\neo_loader\LoaderManagerInterface::getCssFile()
   */
  public function getCssFile() {
    return $this->cssFile;
  }

  /**
   * Sets markup for loader.
   */
  abstract protected function setMarkup();

  /**
   * Sets css file for loader.
   *
   * @deprecated in neo_loader:1.1.0 and is removed from neo_loader:2.0.0.
   *   Declare `css` on the loader's definition, or let the manager derive it
   *   from the id.
   *
   * @phpcs:ignore Drupal.Commenting.Deprecated.DeprecatedWrongSeeUrlFormat
   * @see \Drupal\neo_loader\LoaderManagerInterface::getCssFile()
   */
  abstract protected function setCssFile();

}
