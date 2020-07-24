<?php

namespace Drupal\entity_export_csv\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Field type export plugin manager.
 */
class FieldTypeExportManager extends DefaultPluginManager implements FieldTypeExportManagerInterface {

  /**
   * Constructs a new FieldTypeExportManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/FieldTypeExport', $namespaces, $module_handler, 'Drupal\entity_export_csv\Plugin\FieldTypeExportInterface', 'Drupal\entity_export_csv\Annotation\FieldTypeExport');

    $this->alterInfo('entity_export_csv_field_type_export_info');
    $this->setCacheBackend($cache_backend, 'entity_export_csv_field_type_export_plugins');
  }

  /**
   * {@inheritDoc}
   */
  public function getOptions() {
    $options = [];
    $definitions = $this->getDefinitions();
    $this->sortDefinitions($definitions);
    foreach ($definitions as $plugin_id => $definition) {
      $options[$plugin_id] = $definition['label'];
    }
    return $options;
  }

  /**
   * {@inheritDoc}
   */
  public function getFieldTypeOptions($field_type, $entity_type = NULL, $bundle = NULL, $field_name = NULL) {
    $options = [];
    $definitions = $this->getDefinitions();
    $this->sortDefinitions($definitions);
    foreach ($definitions as $plugin_id => $definition) {
      if (!isset($definition['field_type'])) {
        continue;
      }
      if (in_array($field_type, $definition['field_type']) || empty($definition['field_type']) ) {
        if ($entity_type) {
          if (!empty($definition['entity_type']) && !in_array($entity_type, $definition['entity_type'])) {
            continue;
          }
        }
        if ($bundle) {
          if (!empty($definition['bundle']) && !in_array($bundle, $definition['bundle'])) {
            continue;
          }
        }
        if ($field_name) {
          if (!empty($definition['field_name']) && !in_array($field_name, $definition['field_name'])) {
            continue;
          }
        }
        if (isset($definition['exclusive']) && $definition['exclusive'] === TRUE) {
          return [$plugin_id => $definition['label']];
        }
        $options[$plugin_id] = $definition['label'];
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function removeExcludeDefinitions(array $definitions) {
    $definitions = isset($definitions) ? $definitions : $this->getDefinitions();
    // Exclude 'broken' fallback plugin.
    unset($definitions['broken']);
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    return 'broken';
  }

  /**
   * Sort definitions by weigth descending.
   *
   * @param array $definitions
   *   The definitions.
   */
  protected function sortDefinitions(&$definitions) {
    uasort($definitions, function($a, $b) {
      return $a['weight'] - $b['weight'];
    });
  }

}
