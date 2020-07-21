<?php

namespace Drupal\bynder\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'bynder_metadata' field type.
 *
 * @FieldType(
 *   id = "bynder_metadata",
 *   label = @Translation("Bynder metadata"),
 *   description = @Translation("This field stores a JSON object that describes metadata fetched from the Bynder API."),
 *   category = @Translation("Bynder"),
 *   default_widget = "bynder_metadata",
 *   default_formatter = "bynder_metadata",
 *   no_ui = TRUE,
 * )
 */
class BynderMetadataItem extends FieldItemBase {

  /**
   * The Bynder metadata field name.
   */
  const METADATA_FIELD_NAME = 'bynder_metadata';

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')->setLabel(t('JSON Value'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema['columns']['value']['type'] = 'text';
    $schema['columns']['value']['size'] = 'normal';

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return $this->value === NULL || $this->value === '';
  }

}
