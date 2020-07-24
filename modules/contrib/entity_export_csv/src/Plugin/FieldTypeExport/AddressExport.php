<?php

namespace Drupal\entity_export_csv\Plugin\FieldTypeExport;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_export_csv\Plugin\FieldTypeExportBase;
use Drupal\Core\Field\FieldItemInterface;

/**
 * Defines an Address field type export plugin.
 *
 * @FieldTypeExport(
 *   id = "address_export",
 *   label = @Translation("Address export"),
 *   description = @Translation("Address export"),
 *   weight = 0,
 *   field_type = {
 *     "address",
 *   },
 *   entity_type = {},
 *   bundle = {},
 *   field_name = {},
 *   exclusive = FALSE,
 * )
 */
class AddressExport extends FieldTypeExportBase {

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return [
      'message' => [
        '#markup' => $this->t('Address field type exporter.'),
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
  public function getFieldProperties(FieldDefinitionInterface $definition) {
    $properties = parent::getFieldProperties($definition);
    $properties = array_reverse($properties);
    $properties = $this->moveKeyBefore($properties, 'address_line2', 'address_line1');
    $properties = $this->moveKeyBefore($properties, 'administrative_area', 'sorting_code');
    $properties = $this->moveKeyBefore($properties, 'locality', 'postal_code');
    $this->properties = $properties;
    return $this->properties;
  }

  /**
   * Utility function to move an element before another in an array
   *
   * @param $array
   *   The array to change.
   * @param $find
   *   The key of the element before we want mode before the $move.
   * @param $move
   *   The key of the element we want to move before $find.
   *
   * @return array
   */
  protected function moveKeyBefore($array, $find, $move) {
    if (!isset($array[$find], $array[$move])) {
      return $array;
    }
    $element = [ $move => $array[$move] ];
    $start = array_splice($array, 0, array_search($find, array_keys($array)));
    unset($start[$move]);
    return $start + $element + $array;
  }

}
