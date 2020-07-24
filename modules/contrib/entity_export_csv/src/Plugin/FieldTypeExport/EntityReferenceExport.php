<?php

namespace Drupal\entity_export_csv\Plugin\FieldTypeExport;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\entity_export_csv\Plugin\FieldTypeExportBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemInterface;

/**
 * Defines an Entity Reference field type export plugin.
 *
 * @FieldTypeExport(
 *   id = "entity_reference_export",
 *   label = @Translation("Entity reference export"),
 *   description = @Translation("Entity reference export"),
 *   weight = 0,
 *   field_type = {
 *     "entity_reference",
 *   },
 *   entity_type = {},
 *   bundle = {},
 *   field_name = {},
 *   exclusive = FALSE,
 * )
 */
class EntityReferenceExport extends FieldTypeExportBase {

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return [
      'message' => [
        '#markup' => $this->t('Entity reference field type exporter.'),
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
    if ($format === 'entity_reference_label') {
      $entity = $field_item->get('entity')->getValue();
      if ($entity instanceof EntityInterface) {
        return $entity->label();
      }
    }

    return $field_item->get($property_name)->getValue();
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormatExportOptions(FieldDefinitionInterface $field_definition) {
    $options = parent::getFormatExportOptions($field_definition);
    $options['entity_reference_label'] = $this->t('Label');
    return $options;
  }

}
