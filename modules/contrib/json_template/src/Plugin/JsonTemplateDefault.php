<?php

namespace Drupal\json_template\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default implementation for JSON template plugin.
 */
class JsonTemplateDefault extends PluginBase implements JsonTemplateInterface, ContainerFactoryPluginInterface {

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
   * Json transformer manager interface.
   *
   * @var \Drupal\json_template\Plugin\JsonTransformerManagerInterface
   */
  protected $jsonTransformerManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('theme_handler'),
      $container->get('module_handler'),
      $container->get('plugin.manager.json_template.transformer')
    );
  }

  /**
   * JsonTemplateDefault constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $theme_handler, $module_handler, $json_transformer_manager) {
    $this->themeHandler = $theme_handler;
    $this->moduleHandler = $module_handler;
    $this->jsonTransformerManager = $json_transformer_manager;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return drupal_get_path($this->getProviderType(), $this->pluginDefinition['provider']) . '/' . $this->pluginDefinition['file'];
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

  /**
   * {@inheritdoc}
   */
  public function getTransformer() {
    return $this->pluginDefinition['transformer'];
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
  public function attach(array &$render_array) {
    if (!empty($render_array['#attached']['library'])) {
      $render_array['#attached']['library'][] = $this->getLibrary();
    }
    else {
      $render_array['#attached']['library'] = [$this->getLibrary()];
    }
    $render_array['#attached']['drupalSettings']['jsonTemplate'][$this->getPluginId()] = [
      'template' => $this->getTemplate(),
      'transformer' => $this->getTransformer(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplate() {
    return file_get_contents($this->getPath());
  }

  /**
   * {@inheritdoc}
   */
  public function getLibrary() {
    $definition = $this->jsonTransformerManager->getDefinition($this->getTransformer());
    return $definition['library'];
  }

}
