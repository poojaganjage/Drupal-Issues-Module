<?php

namespace Drupal\commerce_fraud\Entity;

use Drupal\commerce\CommerceSinglePluginCollection;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Rules entity.
 *
 * @ingroup commerce_fraud
 *
 * @ConfigEntityType(
 *   id = "rules",
 *   label = @Translation("Rules"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_fraud\RulesListBuilder",
 *     "form" = {
 *       "default" = "Drupal\commerce_fraud\Form\RulesForm",
 *       "add" = "Drupal\commerce_fraud\Form\RulesForm",
 *       "edit" = "Drupal\commerce_fraud\Form\RulesForm",
 *       "delete" = "Drupal\commerce_fraud\Form\RulesDeleteForm",
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "rules",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "status",
 *     "plugin",
 *     "configuration",
 *     "counter",
 *   },
 *   links = {
 *     "canonical" = "/admin/commerce/rules/{rules}",
 *     "add-form" = "/admin/commerce/rules/add",
 *     "edit-form" = "/admin/commerce/rules/{rules}/edit",
 *     "delete-form" = "/admin/commerce/rules/{rules}/delete",
 *     "collection" = "/admin/commerce/rules",
 *   },
 * )
 */
class Rules extends ConfigEntityBase implements RulesInterface {

  /**
   * The rule ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The rule label.
   *
   * @var string
   */
  protected $label;

  /**
   * The rule counter.
   *
   * @var int
   */
  protected $counter;

  /**
   * The plugin ID.
   *
   * @var string
   */
  protected $plugin;

  /**
   * The plugin configuration.
   *
   * @var array
   */
  protected $configuration = [];

  /**
   * The plugin collection that holds the rule plugin.
   *
   * @var \Drupal\commerce\CommerceSinglePluginCollection
   */
  protected $pluginCollection;

  /**
   * {@inheritdoc}
   */
  public function getCounter() {
    return $this->counter;
  }

  /**
   * {@inheritdoc}
   */
  public function setCounter($counter) {
    $this->counter = $counter;
    return $counter;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    return $this->getPluginCollection()->get($this->plugin);
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function setPluginId($plugin_id) {
    $this->plugin = $plugin_id;
    $this->configuration = [];
    $this->pluginCollection = NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setPluginConfiguration(array $configuration) {
    $this->configuration = $configuration;
    $this->pluginCollection = NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'configuration' => $this->getPluginCollection(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function set($property_name, $value) {
    // Invoke the setters to clear related properties.
    if ($property_name == 'plugin') {
      $this->setPluginId($value);
    }
    elseif ($property_name == 'configuration') {
      $this->setPluginConfiguration($value);
    }
    else {
      return parent::set($property_name, $value);
    }
  }

  /**
   * Gets the plugin collection that holds the rule plugin.
   *
   * Ensures the plugin collection is initialized before returning it.
   *
   * @return \Drupal\commerce\CommerceSinglePluginCollection
   *   The plugin collection.
   */
  protected function getPluginCollection() {
    if (!$this->pluginCollection) {
      $plugin_manager = \Drupal::service('plugin.manager.commerce_fraud_rule');
      $this->pluginCollection = new CommerceSinglePluginCollection($plugin_manager, $this->plugin, $this->configuration, $this);
    }
    return $this->pluginCollection;
  }

}
