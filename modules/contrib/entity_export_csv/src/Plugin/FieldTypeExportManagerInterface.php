<?php

namespace Drupal\entity_export_csv\Plugin;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Define field type export manager interface.
 */
interface FieldTypeExportManagerInterface extends PluginManagerInterface {

  /**
   * Get definition options.
   *
   * @return array
   *   An array of definition options.
   */
  public function getOptions();

  /**
   * Get definition options for a given field type.
   *
   * @param string $field_type
   *   The field type.
   * @param string $entity_type
   *   The entity type id.
   * @param string $bundle
   *   The bundle id.
   * @param string $field_name
   *   The field name.
   *
   * @return array
   *   An array of definition options.
   */
  public function getFieldTypeOptions($field_type, $entity_type = NULL, $bundle = NULL, $field_name = NULL);
}
