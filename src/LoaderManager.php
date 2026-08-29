<?php

declare(strict_types=1);

namespace Drupal\neo_loader;

use Drupal\Component\Plugin\Mapper\MapperInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\YamlDiscoveryDecorator;
use Drupal\neo_loader\Annotation\Loader as LoaderAnnotation;
use Drupal\neo_loader\Attribute\Loader as LoaderAttribute;
use Drupal\neo_loader\Plugin\LoaderPluginInterface;

/**
 * Gathers the loader plugins.
 *
 * A loader can be written as a class or declared as data, and one definition
 * set carries both. A module or a theme declares its own in
 * `{extension}.neo.loader.yml`, keyed by loader id:
 *
 * @code
 *   LOADER_ID:
 *     label: STRING
 *     markup: STRING
 * @endcode
 *
 * `css` and `class` are optional. This is the naming and discovery shape
 * `neo_font` already uses for `{extension}.neo.font.yml`; the module adopts
 * the sibling convention rather than inventing a second one.
 *
 * @see \Drupal\neo_loader\LoaderDefault
 */
class LoaderManager extends DefaultPluginManager implements LoaderManagerInterface, MapperInterface {

  /**
   * {@inheritdoc}
   *
   * A declaration carries only what it says, so every key a definition is read
   * for is defaulted here. `class` is the one that does work: a declaration
   * that names none is given the default loader class, while a class-based
   * definition always carries its own and keeps it.
   *
   * @var array<string, mixed>
   */
  protected $defaults = [
    'id' => '',
    'label' => '',
    'markup' => '',
    'css' => '',
    'class' => LoaderDefault::class,
  ];

  /**
   * The theme handler.
   *
   * Themes declare loaders too, so the theme list is part of both the
   * directories that are scanned and the providers that are allowed to exist.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructs a LoaderManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The discovery cache backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
    ThemeHandlerInterface $theme_handler,
  ) {
    // Both spellings are declared, so discovery reads attributes first and
    // annotations second and one definition set carries both. Declaring only
    // the annotation is the shape core deprecated in 11.2 and removes in 12.
    parent::__construct('Plugin/Loader', $namespaces, $module_handler, LoaderPluginInterface::class, LoaderAttribute::class, LoaderAnnotation::class);
    $this->themeHandler = $theme_handler;
    $this->alterInfo('neo_loader_info');
    $this->setCacheBackend($cache_backend, 'loader', ['loader']);
  }

  /**
   * {@inheritdoc}
   *
   * The class discovery the parent builds is wrapped rather than replaced, so
   * that declarations and classes arrive as one set. The decorator answers
   * `parent::getDefinitions() + $this->decorated->getDefinitions()`, which is
   * what makes a declaration win an id collision against a class plugin.
   */
  protected function getDiscovery() {
    if (!$this->discovery instanceof YamlDiscoveryDecorator) {
      $directories = $this->moduleHandler->getModuleDirectories() + $this->themeHandler->getThemeDirectories();
      $discovery = new YamlDiscoveryDecorator(parent::getDiscovery(), 'neo.loader', $directories);
      $discovery->addTranslatableProperty('label', 'label_context');
      $this->discovery = $discovery;
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   *
   * A theme may declare, so an installed theme satisfies provider existence
   * exactly as an installed module does.
   *
   * @param string $provider
   *   The provider to check for.
   */
  protected function providerExists($provider): bool {
    return $this->moduleHandler->moduleExists($provider) || $this->themeHandler->themeExists($provider);
  }

  /**
   * {@inheritdoc}
   */
  public function getLoaderOptionList() {
    $options = [];
    foreach ($this->getDefinitions() as $definition) {
      $options[$definition['id']] = $definition['label'];
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstances() {
    $loaders = [];
    foreach ($this->getDefinitions() as $id => $definition) {
      $loaders[$id] = $this->createInstance($definition['id']);
    }
    return $loaders;
  }

  /**
   * Finds plugin definitions.
   *
   * The sort casts, because a label discovered from YAML is a
   * \Drupal\Core\StringTranslation\TranslatableMarkup rather than a string
   * and `strnatcasecmp()` is handed it raw. Casting is what sorts declared and
   * class-based loaders into one correct order instead of into a TypeError.
   *
   * @return array
   *   List of definitions to store in cache.
   */
  protected function findDefinitions() {
    $definitions = parent::findDefinitions();
    uasort($definitions, function ($a, $b) {
      return strnatcasecmp((string) $a['label'], (string) $b['label']);
    });
    return $definitions;
  }

}
