<?php

namespace Drupal\commerce_fraud;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages discovery and instantiation of fruad rule plugins.
 *
 * @see \Drupal\commerce_fraud\Annotation\CommerceFraudRule
 * @see plugin_api
 */
class CommerceFraudManager extends DefaultPluginManager {

  /**
   * Constructs a new CommerceFraudManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Commerce/FraudRule', $namespaces, $module_handler, 'Drupal\commerce_fraud\Plugin\Commerce\FraudRule\FraudRuleInterface', 'Drupal\commerce_fraud\Annotation\CommerceFraudRule');

    $this->alterInfo('commerce_fraud_rule_info');
    $this->setCacheBackend($cache_backend, 'commerce_fraud_rule_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    foreach (['id', 'label'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new PluginException(sprintf('The commerce fraud rule %s must define the %s property.', $plugin_id, $required_property));
      }
    }
  }

}
