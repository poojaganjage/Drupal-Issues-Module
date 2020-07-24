<?php

namespace Drupal\entity_export_csv;

/**
 * Interface EntityExportCsvManagerInterface.
 */
interface EntityExportCsvManagerInterface {

  /**
   * The temporary export directory.
   */
  const TEMPORARY_DIRECTORY = 'temporary://entity_export_csv/export';

  /**
   * The private export directory.
   */
  const PRIVATE_DIRECTORY = 'private://entity_export_csv/export';

  /**
   * Returns objects or id of content entity types supported.
   *
   * @param bool $return_object
   *   Should we return an array of object ?
   *
   * @return array
   *   Id or Objects of entity types supported.
   */
  public function getSupportedContentEntityTypes($return_object = TRUE);

  /**
   * Returns an array of content entity types ID enabled.
   *
   * @param bool $return_label
   *   Return an array with the label as value.
   *
   * @return array
   *   An array of entity type id enabled.
   */
  public function getContentEntityTypesEnabled($return_label = FALSE);

  /**
   * Gets the bundles of an entity type.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param bool $return_label
   *   Should we return the array with the label as value ?
   *
   * @return array
   *   An array of bundles.
   */
  public function getBundlesPerEntityType($entity_type_id, $return_label = TRUE);

  /**
   * Gets the bundles enabled of an entity type.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param bool $return_label
   *   Return an array with label as value.
   *
   * @return array
   *   An array of bundles.
   */
  public function getBundlesEnabledPerEntityType($entity_type_id, $return_label = FALSE);

  /**
   * Get the fields as options given an entity type and a bundle
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   * @param bool $return_field_definition
   *   Return the field definitions or label.
   *
   * @return array|\Drupal\Core\Field\FieldDefinitionInterface[]
   *   An array of field label or field definition, keyed by the field name.
   */
  public function getBundleFields($entity_type_id, $bundle, $return_field_definition = FALSE);

  /**
   * Get the fields enabled as options given an entity type and a bundle
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   * @param bool $return_field_definition
   *   Return the field definitions or label.
   *
   * @return array|\Drupal\Core\Field\FieldDefinitionInterface[]
   *   An array of field label or field definition, keyed by the field name.
   */
  public function getBundleFieldsEnabled($entity_type_id, $bundle, $return_field_definition = FALSE);

  /**
   * Get the fields definitions given an entity type and a bundle
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle id.
   *
   * @return array|\Drupal\Core\Field\FieldDefinitionInterface[]
   *   An array of field label or field definition, keyed by the field name.
   */
  public function getBundleFieldDefinitions($entity_type_id, $bundle);

  /**
   * Sort the fields given the fields configuration default value order.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface[] $fields
   *   An array of field definition keyed by the field name.
   * @param array $default_values
   *   An array of field values configuration keyed by the field name.
   */
  public function sortNaturalFields(&$fields, $default_values);

}
