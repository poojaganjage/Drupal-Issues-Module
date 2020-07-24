<?php

namespace Drupal\entity_export_csv\Plugin\FieldTypeExport;

use Drupal\entity_export_csv\Plugin\FieldTypeExportBase;

/**
 * Defines a default field type export plugin.
 *
 * @FieldTypeExport(
 *   id = "default_export",
 *   label = @Translation("Default export"),
 *   description = @Translation("Default export"),
 *   weight = 100,
 *   field_type = {},
 *   entity_type = {},
 *   bundle = {},
 *   field_name = {},
 *   exclusive = FALSE,
 * )
 */
class DefaultExport extends FieldTypeExportBase {

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return [
      'message' => [
        '#markup' => $this->t('Default field type exporter. Extract a property field raw value.'),
      ],
    ];
  }

}
