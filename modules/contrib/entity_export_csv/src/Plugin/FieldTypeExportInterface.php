<?php

namespace Drupal\entity_export_csv\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for Field type export plugins.
 */
interface FieldTypeExportInterface extends PluginInspectionInterface {


  /**
   * Build the configuration form.
   *
   * @param array $form
   *   The configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form_state object.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   *
   * @return mixed
   *   The configuration form.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FieldDefinitionInterface $field_definition);

  /**
   * Validates a configuration form for this plugin.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public function validateConfigurationForm(array $form, FormStateInterface $form_state);

  /**
   * Retrieves the generator description.
   *
   * @return string
   *   The description of this generator.
   */
  public function getDescription();

  /**
   * Retrieves the label.
   *
   * @return string
   *   The label of this plugin.
   */
  public function getLabel();

  /**
   * Provides a human readable summary of the plugin's configuration.
   */
  public function summary();

  /**
   * Export the value of a field.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to export.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $options
   *   An array of additionnal options.
   *
   * @return string
   *   The string value to be export in the CSV file.
   */
  public function export(ContentEntityInterface $entity, $field_definition, $options = []);

  /**
   * Gets the field's properties.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $definition
   *   The field definition.
   *
   * @return array|\Drupal\Core\TypedData\DataDefinitionInterface[]
   *   The field properties.
   */
  public function getFieldProperties(FieldDefinitionInterface $definition);

  /**
   * Massage the field item property value to CSV value.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $field_item
   *   The field item.
   * @param string $property_name
   *   The property name.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $options
   *   An array of optional options.
   *
   * @return mixed
   *   The CSV value.
   */
  public function massageExportPropertyValue(FieldItemInterface $field_item, $property_name, FieldDefinitionInterface $field_definition, $options = []);

  /**
   * Get the header columns for a field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   *
   * @return array
   *   An array of header columns.
   */
  public function getHeaders(FieldDefinitionInterface $field_definition);

  /**
   * Get the columns to generate during the export.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   *
   * @return int
   *   The number of columns to generate.
   */
  public function getColumns(FieldDefinitionInterface $field_definition);

  /**
   * Get the header label for a field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   *
   * @return string
   *   The header label.
   */
  public function getHeaderLabel(FieldDefinitionInterface $field_definition);

  /**
   * Import a value into a field.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to export.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param string $property_name
   *   The field property to import.
   * @param array $options
   *   An array of additionnal options.
   *
   * @return string
   *   The string value to be export in the CSV file.
   */
  public function import(ContentEntityInterface $entity, $field_definition, $property_name = '', $options = []);

}
