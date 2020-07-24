<?php

namespace Drupal\entity_export_csv\Plugin\FieldTypeExport;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\entity_export_csv\Plugin\FieldTypeExportBase;
use Drupal\Core\Field\FieldItemInterface;

/**
 * Defines a Link field type export plugin.
 *
 * @FieldTypeExport(
 *   id = "link_export",
 *   label = @Translation("Link export"),
 *   description = @Translation("Link export"),
 *   weight = 0,
 *   field_type = {
 *     "link",
 *   },
 *   entity_type = {},
 *   bundle = {},
 *   field_name = {},
 *   exclusive = FALSE,
 * )
 */
class LinkExport extends FieldTypeExportBase {

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return [
      'message' => [
        '#markup' => $this->t('Link field type exporter.'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function massageExportPropertyValue(FieldItemInterface $field_item, $property_name, FieldDefinitionInterface $field_definition, $options = []) {
    return parent::massageExportPropertyValue($field_item, $property_name, $field_definition, $options);
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormatExportOptions(FieldDefinitionInterface $field_definition) {
    $options = parent::getFormatExportOptions($field_definition);
    return $options;
  }

}
