<?php

namespace Drupal\entity_export_csv\Plugin\FieldTypeExport;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_export_csv\Plugin\FieldTypeExportBase;
use Drupal\Core\Field\FieldItemInterface;

/**
 * Defines a Geolocation field type export plugin.
 *
 * @FieldTypeExport(
 *   id = "geolocation_export",
 *   label = @Translation("Geolocation export"),
 *   description = @Translation("Geolocation export"),
 *   weight = 0,
 *   field_type = {
 *     "geolocation",
 *   },
 *   entity_type = {},
 *   bundle = {},
 *   field_name = {},
 *   exclusive = FALSE,
 * )
 */
class GeolocationExport extends FieldTypeExportBase {

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return [
      'message' => [
        '#markup' => $this->t('Geolocation field type exporter.'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FieldDefinitionInterface $field_definition) {
    $build = parent::buildConfigurationForm($form, $form_state, $field_definition);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function massageExportPropertyValue(FieldItemInterface $field_item, $property_name, FieldDefinitionInterface $field_definition, $options = []) {
    return parent::massageExportPropertyValue($field_item, $property_name, $field_definition, $options);
  }


}
