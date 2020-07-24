<?php

namespace Drupal\entity_export_csv\Plugin\FieldTypeExport;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\entity_export_csv\Plugin\FieldTypeExportBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\file\FileInterface;

/**
 * Defines a File field type export plugin.
 *
 * @FieldTypeExport(
 *   id = "file_export",
 *   label = @Translation("File export"),
 *   description = @Translation("File export"),
 *   weight = 0,
 *   field_type = {
 *     "file",
 *     "image",
 *   },
 *   entity_type = {},
 *   bundle = {},
 *   field_name = {},
 *   exclusive = FALSE,
 * )
 */
class FileExport extends FieldTypeExportBase {

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return [
      'message' => [
        '#markup' => $this->t('File field type exporter.'),
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
    $entity = $field_item->get('entity')->getValue();
    if ($entity instanceof FileInterface && $property_name === 'target_id') {
      if ($format === 'filename') {
        return basename($entity->getFileUri());
      }
      elseif ($format === 'uri') {
        return $entity->getFileUri();
      }
    }

    return $field_item->get($property_name)->getValue();
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormatExportOptions(FieldDefinitionInterface $field_definition) {
    $options = parent::getFormatExportOptions($field_definition);
    $options['filename'] = $this->t('Filename');
    $options['uri'] = $this->t('Uri');
    return $options;
  }

}
