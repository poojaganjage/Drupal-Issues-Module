<?php

namespace Drupal\bynder\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'bynder_metadata' formatter.
 *
 * @FieldFormatter(
 *   id = "bynder_metadata",
 *   label = @Translation("Bynder metadata"),
 *   field_types = {
 *     "bynder_metadata"
 *   }
 * )
 */
class BynderMetadataFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    return [];
  }

}
