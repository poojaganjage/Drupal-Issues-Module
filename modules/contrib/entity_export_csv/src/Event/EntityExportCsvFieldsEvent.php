<?php

namespace Drupal\entity_export_csv\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Defines the fields event.
 *
 * This event allow modules to alter the fields supported / enabled per entity
 * type end bundle. Module can also add pseudo field, based of an existing
 * field, if they want export same field several times in distinct format.
 *
 */
class EntityExportCsvFieldsEvent extends Event {

  /**
   * An array of field definition (or label) keyed by the field name.
   *
   * @var array|\Drupal\Core\Field\FieldDefinitionInterface[]
   */
  protected $fields;

  /**
   * The entity type id.
   *
   * @var string
   */
  protected $entity_type_id;

  /**
   * The bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * Return the field definition or the label (if false).
   *
   * @var bool
   */
  protected $returnFieldDefinition = FALSE;

  /**
   * Constructs a new EntityExportCsvFieldsSupportedEvent.
   *
   * @param array $fields
   *   The fields supported.
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   * @param bool $return_field_definition
   *   Return the field definition or the label if false.
   */
  public function __construct($fields, $entity_type_id, $bundle, $return_field_definition) {
    $this->fields = $fields;
    $this->entity_type_id = $entity_type_id;
    $this->bundle = $bundle;
    $this->returnFieldDefinition = $return_field_definition;
  }

  /**
   * Gets the entity.
   *
   * @return string
   *   Gets the entity type id.
   */
  public function getEntityTypeId() {
    return $this->entity_type_id;
  }

  /**
   * Gets the bundle.
   *
   * @return string
   *   The bundle.
   */
  public function getBundle() {
    return $this->bundle;
  }

  /**
   * Should return the field definition ?
   *
   * @return bool
   *   Return the field definition or the label (if false).
   */
  public function shouldReturnFieldDefinition() {
    return $this->returnFieldDefinition;
  }

  /**
   * Gets the fields.
   *
   * @return array $fields
   *   An array of field definitions, or label, keyed by field_name.
   */
  public function getFields() {
    return $this->fields;
  }

  /**
   * Sets the supported fields.
   *
   * @param array $fields
   *   An array of field definitions, or label, keyed by field_name.
   *
   * @return $this
   */
  public function setFields($fields) {
    $this->fields = $fields;
    return $this;
  }

}
