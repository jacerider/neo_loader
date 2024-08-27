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
   * @var string
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
   * The plugin label.
   *
   * @var string
   */
  protected $label;

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
    $this->path = 'src';
    $this->markup = $this->setMarkup();
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
   */
  public function getCssFile() {
    return $this->cssFile;
  }

  /**
   * Function to get label.
   *
   * @return mixed
   *   Return the label.
   */
  public function getLabel() {
    return $this->configuration['label'];
  }

  /**
   * Sets markup for loader.
   */
  abstract protected function setMarkup();

  /**
   * Sets css file for loader.
   */
  abstract protected function setCssFile();

}
