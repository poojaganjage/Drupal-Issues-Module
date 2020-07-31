<?php

namespace Drupal\commerce_fraud\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Defines the interface for rule configuration entities.
 *
 * Stores configuration for rule plugins.
 */
interface RulesInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface {

  /**
   * Gets the rule counter.
   *
   * @return string
   *   The rule counter.
   */
  public function getCounter();

  /**
   * Sets the rule counter.
   *
   * @param int $counter
   *   The rule counter.
   *
   * @return $this
   */
  public function setCounter($counter);

  /**
   * Gets the rule plugin.
   *
   * @return \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayInterface
   *   The rule plugin.
   */
  public function getPlugin();

  /**
   * Gets the rule plugin ID.
   *
   * @return string
   *   The rule plugin ID.
   */
  public function getPluginId();

  /**
   * Sets the rule plugin ID.
   *
   * @param string $plugin_id
   *   The rule plugin ID.
   *
   * @return $this
   */
  public function setPluginId($plugin_id);

  /**
   * Gets the rule plugin configuration.
   *
   * @return array
   *   The rule plugin configuration.
   */
  public function getPluginConfiguration();

  /**
   * Sets the rule plugin configuration.
   *
   * @param array $configuration
   *   The rule plugin configuration.
   *
   * @return $this
   */
  public function setPluginConfiguration(array $configuration);

}
