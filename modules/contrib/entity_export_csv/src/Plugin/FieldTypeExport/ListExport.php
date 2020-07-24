<?php

namespace Drupal\entity_export_csv\Plugin\FieldTypeExport;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\entity_export_csv\Plugin\FieldTypeExportBase;
use Drupal\Core\Field\FieldItemInterface;

/**
 * Defines a List field type export plugin.
 *
 * @FieldTypeExport(
 *   id = "list_export",
 *   label = @Translation("List export"),
 *   description = @Translation("List export"),
 *   weight = 0,
 *   field_type = {
 *     "list_string",
 *     "list_float",
 *     "list_integer",
 *   },
 *   entity_type = {},
 *   bundle = {},
 *   field_name = {},
 *   exclusive = FALSE,
 * )
 */
class ListExport extends FieldTypeExportBase {

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return [
      'message' => [
        '#markup' => $this->t('List field type exporter.'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function massageExportPropertyValue(FieldItemInterface $field_item, $property_name, FieldDefinitionInterface $field_definition, $options = []) {
    if ($field_item->isEmpty()) {
      return NULL;
    }
    $configuration = $this->getConfiguration();
    if (empty($configuration['format'])) {
      return $field_item->get($property_name)->getValue();
    }

    $format = $configuration['format'];
    if ($format === 'list_label') {
      $value = $field_item->get($property_name)->getValue();
      $allowed_values = $field_definition->getFieldStorageDefinition()->getSetting('allowed_values');
      return isset($allowed_values[$value]) ? $allowed_values[$value] : $value;
    }

    return $field_item->get($property_name)->getValue();
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormatExportOptions(FieldDefinitionInterface $field_definition) {
    $options = parent::getFormatExportOptions($field_definition);
    $options['list_label'] = $this->t('Label');
    return $options;
  }

}
