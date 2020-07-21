<?php

namespace Drupal\bynder\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'bynder_metadata' widget.
 *
 * @FieldWidget(
 *   id = "bynder_metadata",
 *   label = @Translation("Bynder metadata"),
 *   field_types = {
 *     "bynder_metadata"
 *   },
 * )
 */
class BynderMetadataWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    return $element;
  }

}
