<?php

namespace Drupal\json_template\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default implementation of JSON transformer plugin.
 */
class JsonTransformerDefault extends PluginBase implements JsonTransformerInterface, ContainerFactoryPluginInterface {

  /**
   * Theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Install profile used at installation.
   *
   * @var string
   */
  protected $installProfile;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('theme_handler'),
      $container->getParameter('install_profile')
    );
  }

  /**
   * JsonTransformerDefault constructor.
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              ModuleHandlerInterface $module_handler,
                              ThemeHandlerInterface $theme_handler,
                              $install_profile) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->themeHandler = $theme_handler;
    $this->installProfile = $install_profile;
  }

  /**
   * {@inheritdoc}
   */
  public function getProviderType() {
    $provider = $this->pluginDefinition['provider'];
    if ($this->themeHandler->themeExists($provider)) {
      return 'theme';
    }
    if ($this->moduleHandler->moduleExists($provider)) {
      return 'module';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraryProvider() {
    $name = explode('/', $this->pluginDefinition['library'], 2)[0];
    if ($name === 'core') {
      return ['type' => 'core', 'name' => 'core'];
    }
    if ($this->themeHandler->themeExists($name)) {
      return ['type' => 'theme', 'name' => $name];
    }
    if ($this->moduleHandler->moduleExists($name)) {
      return ['type' => 'module', 'name' => $name];
    }
    if ($name === $this->installProfile) {
      return ['type' => 'profile', 'name' => $name];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTransformer() {
    // In case of absolute path return as is.
    if (strpos($this->pluginDefinition['transformer'], '/') === 0) {
      return $this->pluginDefinition['transformer'];
    }
    // Library declared in the same theme/module.
    if ($this->pluginDefinition['provider'] === $this->getLibraryProvider()['name']) {
      return $this->pluginDefinition['transformer'];
    }
    // We need to construct full path.
    return base_path() . drupal_get_path($this->getProviderType(), $this->pluginDefinition['provider']) . '/' . $this->pluginDefinition['transformer'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibrary() {
    return explode('/', $this->pluginDefinition['library'], 2)[1];
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return (string) $this->pluginDefinition['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return (string) $this->pluginDefinition['description'];
  }

}
