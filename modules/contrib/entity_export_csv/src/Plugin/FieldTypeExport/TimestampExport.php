<?php

namespace Drupal\entity_export_csv\Plugin\FieldTypeExport;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_export_csv\Plugin\FieldTypeExportBase;
use Drupal\Core\Field\FieldItemInterface;

/**
 * Defines a Timestamp field type export plugin.
 *
 * @FieldTypeExport(
 *   id = "timestamp_export",
 *   label = @Translation("Timestamp export"),
 *   description = @Translation("Timestamp export"),
 *   weight = 0,
 *   field_type = {
 *     "timestamp",
 *     "created",
 *     "changed",
 *   },
 *   entity_type = {},
 *   bundle = {},
 *   field_name = {},
 *   exclusive = FALSE,
 * )
 */
class TimestampExport extends FieldTypeExportBase {

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return [
      'message' => [
        '#markup' => $this->t('Timestamp field type exporter.'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FieldDefinitionInterface $field_definition) {
    $configuration = $this->getConfiguration();
    $build = parent::buildConfigurationForm($form, $form_state, $field_definition);
    $date_formats = [];
    $date_formats[''] = $this->t('None');
    foreach ($this->entityTypeManager->getStorage('date_format')->loadMultiple() as $machine_name => $value) {
      $date_formats[$machine_name] = $this->t('@name format: @date', ['@name' => $value->label(), '@date' => $this->dateFormatter->format(REQUEST_TIME, $machine_name)]);
    }
    $date_formats['custom'] = $this->t('Custom');

    $build['custom_date_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom date format'),
      '#description' => $this->t('See <a href="http://php.net/manual/function.date.php" target="_blank">the documentation for PHP date formats</a>.'),
      '#default_value' => !empty($configuration['custom_date_format']) ? $configuration['custom_date_format'] : '',
    ];

    $build['custom_date_format']['#states']['visible'][] = [
      ':input[name="fields[' . $field_definition->getName() .'][form][options][format]"]' => ['value' => 'custom'],
    ];

    return $build;
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

    $langcode = NULL;
    $timezone = NULL;
    $date_format = $configuration['format'];
    $custom_date_format = !empty($configuration['custom_date_format']) ? $configuration['custom_date_format'] : '';
    $value = $field_item->get($property_name)->getValue();

    // If an RFC2822 date format is requested, then the month and day have to
    // be in English. @see http://www.faqs.org/rfcs/rfc2822.html
    if ($date_format === 'custom' && ($custom_date_format === 'r')) {
      $langcode = 'en';
    }
    return $this->dateFormatter->format($value, $date_format, $custom_date_format, $timezone, $langcode);
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormatExportOptions(FieldDefinitionInterface $field_definition) {
    $options = parent::getFormatExportOptions($field_definition);
    $date_formats = [];
    foreach ($this->entityTypeManager->getStorage('date_format')->loadMultiple() as $machine_name => $value) {
      $date_formats[$machine_name] = $this->t('@name format: @date', ['@name' => $value->label(), '@date' => $this->dateFormatter->format(REQUEST_TIME, $machine_name)]);
    }
    $date_formats['custom'] = $this->t('Custom');
    return $options + $date_formats;
  }

}
