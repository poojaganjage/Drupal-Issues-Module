<?php

namespace Drupal\entity_export_csv\Plugin\FieldTypeExport;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;
use Drupal\entity_export_csv\Plugin\FieldTypeExportBase;
use Drupal\Core\Field\FieldItemInterface;

/**
 * Defines a Datetime field type export plugin.
 *
 * @FieldTypeExport(
 *   id = "datetime_export",
 *   label = @Translation("Datetime export"),
 *   description = @Translation("Datetime export"),
 *   weight = 0,
 *   field_type = {
 *     "datetime",
 *   },
 *   entity_type = {},
 *   bundle = {},
 *   field_name = {},
 *   exclusive = FALSE,
 * )
 */
class DateTimeExport extends FieldTypeExportBase {

  /**
   * An array of date_format entity keyed by format and langcode.
   *
   * @var array
   */
  protected $dateFormats = [];

  /**
   * Date.
   *
   * @var date
   */
  protected $date;

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return [
      'message' => [
        '#markup' => $this->t('Datetime field type exporter.'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FieldDefinitionInterface $field_definition) {
    $configuration = $this->getConfiguration();
    $build = parent::buildConfigurationForm($form, $form_state, $field_definition);
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

    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $timezone = NULL;
    $format = NULL;
    $settings = [
      'langcode' => $langcode,
    ];
    $date_format_id = $configuration['format'];
    $custom_date_format = !empty($configuration['custom_date_format']) ? $configuration['custom_date_format'] : '';
    // If an RFC2822 date format is requested, then the month and day have to
    // be in English. @see http://www.faqs.org/rfcs/rfc2822.html
    if ($date_format_id === 'custom' && ($custom_date_format === 'r')) {
      $langcode = 'en';
      $settings['langcode'] = $langcode;
    }
    if (!empty($custom_date_format) && $date_format_id === 'custom') {
      $format = $custom_date_format;
    }
    else {
      if ($date_format = $this->getDateFormat($date_format_id, $langcode)) {
        $format = $date_format->getPattern();
      }
    }

    $date = $field_item->date;
    if ($date instanceof DrupalDateTime && $format) {
      return $date->format($format, $settings);
    }
    // Fallback to the raw value.
    return $field_item->get($property_name)->getValue();
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

  /**
   * Loads the given format pattern for the given langcode.
   *
   * @param string $format
   *   The machine name of the date format.
   * @param string $langcode
   *   The langcode of the language to use.
   *
   * @return \Drupal\Core\Datetime\DateFormatInterface|null
   *   The configuration entity for the date format in the given language for
   *   non-custom formats, NULL otherwise.
   */
  protected function getDateFormat($format, $langcode) {
    if (!isset($this->dateFormats[$format][$langcode])) {
      $original_language = $this->languageManager->getConfigOverrideLanguage();
      $this->languageManager->setConfigOverrideLanguage(new Language(['id' => $langcode]));
      $this->dateFormats[$format][$langcode] = $this->entityTypeManager->getStorage('date_format')->load($format);
      $this->languageManager->setConfigOverrideLanguage($original_language);
    }
    return $this->dateFormats[$format][$langcode];
  }

}
