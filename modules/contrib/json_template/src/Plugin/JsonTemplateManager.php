<?php

namespace Drupal\json_template\Plugin;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupal\Core\Plugin\Factory\ContainerFactory;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Plugin manager class for json template plugins.
 */
class JsonTemplateManager extends DefaultPluginManager implements JsonTemplateManagerInterface {

  /**
   * Theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Provides some default values for all json template plugins.
   *
   * @var array
   */
  protected $defaults = [
    // The plugin id. Set by the plugin system based on the top-level YAML key.
    'id' => NULL,
    // The title for the template.
    'title' => '',
    // Description of the template.
    'description' => '',
    // Path to template file.
    'file' => '',
    // Transformer id (handlebars, mustache or other).
    'transformer' => NULL,
    // Block plugins template is available for.
    'available_for' => [],
    // Default plugin class.
    'class' => 'Drupal\json_template\Plugin\JsonTemplateDefault',
  ];

  /**
   * {@inheritdoc}
   */
  public function __construct(ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler, CacheBackendInterface $cache_backend) {
    // Skip calling the parent constructor, since that assumes annotation-based
    // discovery.
    $this->moduleHandler = $module_handler;
    $this->themeHandler = $theme_handler;
    $this->factory = new ContainerFactory($this, 'Drupal\json_template\Plugin\JsonTemplateInterface');
    $this->alterInfo('json_template');
    $this->setCacheBackend($cache_backend, 'json_template_plugins', ['json_template']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $yaml_discovery = new YamlDiscovery('json_template.templates', $this->moduleHandler->getModuleDirectories() + $this->themeHandler->getThemeDirectories());
      $yaml_discovery->addTranslatableProperty('title', 'title_context');
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($yaml_discovery);
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  protected function providerExists($provider) {
    return $this->moduleHandler->moduleExists($provider) || $this->themeHandler->themeExists($provider);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitionsForId(string $id) {
    // @TODO Cache this.
    return array_filter($this->getDefinitions(), static function ($a) use ($id) {
      if (in_array($id, $a['available_for'], FALSE)) {
        return TRUE;
      }
    });
  }

}
