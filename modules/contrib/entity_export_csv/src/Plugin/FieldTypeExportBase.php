<?php

namespace Drupal\entity_export_csv\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\DependencyTrait;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Field type export plugins.
 */
abstract class FieldTypeExportBase extends PluginBase implements FieldTypeExportInterface, ContainerFactoryPluginInterface {

  use DependencyTrait;
  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Datetime\DateFormatterInterface definition.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Drupal\Core\Extension\ModuleHandlerInterface definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Drupal\Core\Entity\EntityRepositoryInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Drupal\Core\Entity\EntityFieldManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The field properties.
   *
   * @var \Drupal\Core\TypedData\DataDefinitionInterface[]
   */
  protected $properties = NULL;

  /**
   * LogGeneratorBase constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, DateFormatterInterface $date_formatter, ModuleHandlerInterface $module_handler, EntityRepositoryInterface $entity_repository, EntityFieldManagerInterface $entity_field_manager, LanguageManagerInterface $language_manager, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
    $this->moduleHandler = $module_handler;
    $this->entityRepository = $entity_repository;
    $this->entityFieldManager = $entity_field_manager;
    $this->languageManager = $language_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
      $container->get('module_handler'),
      $container->get('entity.repository'),
      $container->get('entity_field.manager'),
      $container->get('language_manager'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FieldDefinitionInterface $field_definition) {
    $build = [];
    $configuration = $this->getConfiguration();
    $field_name = $field_definition->getName();
    $build['header'] = [
      '#type' => 'radios',
      '#title' => $this->t('Header'),
      '#options' => $this->getHeaderOptions(),
      '#default_value' => $configuration['header'],
      '#required' => TRUE,
      '#attributes' => [
        'class' => [
          'inline-radios',
        ],
      ],
    ];

    $properties = $this->getPropertyExportOptions($field_definition);
    $main_property = $this->getMainPropertyName($field_definition);
    $allow_export_multiple_properties = $this->allowExportMultipleProperties($field_definition);
    if (isset($properties[$main_property])) {
      $default_property = $main_property;
    }
    else {
      $default_property = reset(array_keys($properties));
    }
    $build['property'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Property'),
      '#options' => $properties,
      '#default_value' => !empty($configuration['property']) ? $configuration['property'] : [$default_property],
      '#required' => (bool) !empty($properties),
      '#attributes' => [
        'class' => [
          'inline-radios',
        ],
      ],
    ];

    $build['property_separator'] = [
      '#type' => 'select',
      '#title' => $this->t('Property separator'),
      '#description' => $this->t('The separator used if multiple properties are selected to be exported in one column.'),
      '#options' => $this->getPropertyColumnSeparatorOptions($field_definition),
      '#default_value' => $configuration['property_separator'],
      '#access' => $allow_export_multiple_properties,
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $field_name .'][form][options][property_separate_column]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $build['property_separate_column'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Export each property selected into a separate column'),
      '#default_value' => $configuration['property_separate_column'],
      '#access' => $allow_export_multiple_properties,
    ];

    $build['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Format'),
      '#options' => $this->getFormatExportOptions($field_definition),
      '#default_value' => !empty($configuration['format']) ? $configuration['format'] : '',
    ];

    $max_columns = $this->getMaxColumns();
    if ($this->fieldDefinitionIsMultiple($field_definition) && $max_columns > 1) {
      $columns = ['' => $this->t('Same column')];
      for ($i = 2 ; $i < $max_columns + 1 ; $i++) {
        $columns[$i] = $this->t('@number columns', ['@number' => $i]);
      }
      $build['explode'] = [
        '#type' => 'select',
        '#title' => $this->t('Columns to explode multiple values'),
        '#description' => $this->t('Select the number of columns you want to export this multiple field. For this field, this setting will generate the number of columns set. Select <em>Same column</em> to export all values into a unique column.'),
        '#options' => $columns,
        '#default_value' => isset($configuration['explode']) ? $configuration['explode'] : '',
      ];
    }

    if ($this->fieldDefinitionIsMultiple($field_definition)) {
      $build['separator'] = [
        '#type' => 'select',
        '#title' => $this->t('Separator'),
        '#options' => $this->getSeparatorsOptions(),
        '#default_value' => $configuration['separator'],
        "#required" => TRUE,
        '#states' => [
          'visible' => [
            ':input[name="fields[' . $field_name .'][form][options][explode]"]' => ['value' => ''],
          ],
        ],
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array $form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'header' => 'label',
      'property_separate_column' => FALSE,
      'property_separator' => '|',
      'separator' => '|',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $merged_array = NestedArray::mergeDeepArray([$this->defaultConfiguration(), $this->configuration], TRUE);
    return ['id' => $this->getPluginId()] + $merged_array;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $plugin_definition = $this->getPluginDefinition();
    return isset($plugin_definition['description']) ? $plugin_definition['description'] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    $plugin_definition = $this->getPluginDefinition();
    return isset($plugin_definition['label']) ? $plugin_definition['label'] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $summary = '';
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function export(ContentEntityInterface $entity, $field_definition, $options = []) {
    $configuration = $this->getConfiguration();
    $row = [];
    $values = [];
    $field_name = $field_definition->getName();

    $property_names = $this->getPropertiesSelected($field_definition);
    if ($entity->hasField($field_name)) {
      /** @var \Drupal\Core\Field\FieldItemListInterface $field_items */
      $field_items = $entity->get($field_name);
      /** @var \Drupal\Core\Field\FieldItemInterface $field_item */
      foreach ($field_items as $index => $field_item) {
        foreach ($property_names as $property_name) {
          $values[$index][$property_name] = $this->massageExportPropertyValue($field_item, $property_name, $field_definition, $options);
        }
      }
    }

    $values_per_property = [];
    foreach ($values as $index => $properties) {
      foreach ($properties as $property_name => $property_value) {
        $values_per_property[$property_name][$index] = $property_value;
      }
    }

    $columns = $this->getColumns($field_definition);
    if ($columns === 1) {
      if ($this->propertiesInSeparateColumns()) {
        foreach ($property_names as $property_name) {
          $row[] = implode($configuration['separator'], $values_per_property[$property_name]);
        }
      }
      else {
        foreach ($values as $index => $properties) {
          $properties = array_filter($properties);
          $values[$index] = implode($this->getPropertyColumnSeparator(), $properties);
        }
        $row[] = implode($configuration['separator'], $values);
      }

    }
    else {
      for($i = 0; $i < $columns; $i++) {
        if ($this->propertiesInSeparateColumns()) {
          foreach ($property_names as $property_name) {
            $row[] = isset($values[$i][$property_name]) ? $values[$i][$property_name] : NULL;
          }
        }
        else {
          $properties = isset($values[$i]) ? $values[$i] : [];
          $properties = array_filter($properties);
          $row[] = implode($this->getPropertyColumnSeparator(), $properties);
        }
      }
    }
    return $row;
  }

  /**
   * Get the field properties selected.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   *
   * @return array|mixed
   *   An array of properties selected.
   */
  protected function getPropertiesSelected(FieldDefinitionInterface $field_definition) {
    $configuration = $this->getConfiguration();
    $property_names = !empty($configuration['property']) ? $configuration['property'] : [];
    if (empty($property_names)) {
      $storage = $field_definition->getFieldStorageDefinition();
      $property_names = [$storage->getMainPropertyName()];
    }

    // For backward compatibility, ensure the property is an array.
    if (!is_array($property_names)) {
      $property_names = [$property_names];
    }
    $property_names = array_filter($property_names);
    return $property_names;
  }

  /**
   * Should properties be exported in separated columns ?
   *
   * @return bool
   *   TRUE if properties should be exported in separated columns. Otherwise
   *   FALSE.
   */
  protected function propertiesInSeparateColumns() {
    $configuration = $this->getConfiguration();
    return (bool) $configuration['property_separate_column'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldProperties(FieldDefinitionInterface $definition) {
    if (is_null($this->properties)) {
      $storage = $definition->getFieldStorageDefinition();
      $properties = $storage->getPropertyDefinitions();
      // Filter out all computed properties, these cannot be set.
      $properties = array_filter($properties, function (DataDefinitionInterface $definition) {
        return !$definition->isComputed();
      });

      if ($definition->getType() === 'image') {
        unset($properties['width'], $properties['height']);
      }
      $this->properties = $properties;
    }
    return $this->properties;
  }

  /**
   * {@inheritdoc}
   */
  public function massageExportPropertyValue(FieldItemInterface $field_item, $property_name, FieldDefinitionInterface $field_definition, $options = []) {
    if ($field_item->isEmpty()) {
      return NULL;
    }
    if (empty($property_name)) {
      return NULL;
    }
    return $field_item->get($property_name)->getValue();
  }

  /**
   * Is the field is multiple ?
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   *
   * @return bool|int
   *   Return TRUE if the field is multiple.
   */
  protected function fieldDefinitionIsMultiple(FieldDefinitionInterface $field_definition) {
    $cardinality = $field_definition->getFieldStorageDefinition()->getCardinality();
    if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED || $cardinality > 1) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeaders(FieldDefinitionInterface $field_definition) {
    $headers = [];
    $columns = $this->getColumns($field_definition);
    $header_label = $this->getHeaderLabel($field_definition);
    $properties_selected = $this->getPropertiesSelected($field_definition);
    if ($columns === 1) {
      if ($this->propertiesInSeparateColumns()) {
        foreach ($properties_selected as $property_name) {
          $headers[] = $header_label . '__' . $this->getPropertyLabel($property_name, $field_definition);
        }
      }
      else {
        $headers[] = $header_label;
      }
    }
    else {
      for($i = 0; $i < $columns; $i++) {
        if ($this->propertiesInSeparateColumns()) {
          foreach ($properties_selected as $property_name) {
            $headers[] = $header_label . '__' . $this->getPropertyLabel($property_name, $field_definition) . '__' . $i;
          }
        }
        else {
          $headers[$i] = $header_label . '__' . $i;
        }
      }
    }
    return $headers;
  }

  /**
   * {@inheritdoc}
   */
  public function getColumns(FieldDefinitionInterface $field_definition) {
    $columns = 1;
    if (!$this->fieldDefinitionIsMultiple($field_definition)) {
      return $columns;
    }
    $configuration = $this->getConfiguration();
    $max_columns = $this->getMaxColumns();
    if (!empty($configuration['explode']) && $max_columns > 1) {
      $columns = (integer) $configuration['explode'];
    }
    // The max column setting can be changed after a configuration has been
    // saved.
    if ($columns > $max_columns) {
      $columns = $max_columns;
    }
    return $columns;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeaderLabel(FieldDefinitionInterface $field_definition) {
    $configuration = $this->getConfiguration();
    $header_type = $configuration['header'];
    $header = '';
    if ($header_type === 'field_name') {
      $header = $field_definition->getName();
    }
    elseif ($header_type === 'label') {
      $header = (string) $field_definition->getLabel();
    }
    return $header;
  }


  /**
   * Get the property header label.
   *
   * @param $property_name
   *   The property name.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   *
   * @return mixed|string
   *   The property header label.
   */
  protected function getPropertyLabel($property_name, FieldDefinitionInterface $field_definition) {
    $configuration = $this->getConfiguration();
    $header_type = $configuration['header'];
    $properties = $this->getPropertyExportOptions($field_definition);
    $header = '';
    if ($header_type === 'field_name') {
      $header = $property_name;
    }
    elseif ($header_type === 'label') {
      $header = isset($properties[$property_name]) ? $properties[$property_name] : $property_name;
    }
    return $header;
  }

  /**
   * Get the max columns for multiple fields.
   *
   * @return int
   *   The max columns.
   */
  protected function getMaxColumns() {
    return (integer) $this->configFactory->get('entity_export_csv.settings')->get('multiple.columns') ?: 1;
  }

  /**
   * Get the separator options when exporting in a single column.
   *
   * @return array
   *   An array of options separator.
   */
  protected function getSeparatorsOptions() {
    $options = [
      '|' => $this->t('Pipe (|)'),
      '.' => $this->t('Dot (.)'),
      ';' => $this->t('Semicolon (;)'),
      '__' => $this->t('Double underscore (__)'),
    ];
    return $options;
  }

  /**
   * Get the properties options to export.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   *
   * @return array
   *   An array of properties.
   */
  protected function getPropertyExportOptions(FieldDefinitionInterface $field_definition) {
    $properties = $this->getFieldProperties($field_definition);
    $options = [];
    foreach ($properties as $property_name => $property) {
      $options[$property_name] = $property->getLabel();
    }
    return $options;
  }

  /**
   * Get the format options to export.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   *
   * @return array
   *   An array of format options.
   */
  protected function getFormatExportOptions(FieldDefinitionInterface $field_definition) {
    $options = [];
    $options[''] = $this->t('None');
    return $options;
  }


  /**
   * Get the property separator options.
   *
   * @return array
   */
  protected function getPropertyColumnSeparatorOptions() {
    $options = [];
    $options['|'] = $this->t('Separator pipe ( | )');
    $options[''] = $this->t('Separator blank ( )');
    $options['-'] = $this->t('Separator dash ( - )');
    $options['_'] = $this->t('Separator underscore ( _ )');
    $options['eol'] = $this->t('Separator end of line ( EOL )');
    return $options;
  }


  /**
   * Get the property separator selected.
   *
   * @return mixed|string
   */
  protected function getPropertyColumnSeparator() {
    $configuration = $this->getConfiguration();
    $separator = $configuration['property_separator'];
    if (empty($separator)) {
      $separator = ' ';
    }
    elseif ($separator === 'eol') {
      $separator = PHP_EOL;
    }
    return $separator;
  }

  /**
   * Get the main property name of a field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   *
   * @return string
   *   The main property name.
   */
  protected function getMainPropertyName(FieldDefinitionInterface $field_definition) {
    $main_property_name = $field_definition->getFieldStorageDefinition()->getMainPropertyName();
    return $main_property_name;
  }

  /**
   * Get the header export options.
   *
   * @return array
   *   An array of header export.
   */
  protected function getHeaderOptions() {
    $options = [
      'label' => $this->t('Label'),
      'field_name' => $this->t('Field name'),
    ];
    return $options;
  }

  /**
   * Default method to allow to export multiple properties.
   *
   * Any plugin can override this method to enforce the behavior.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   *
   * @return bool
   *   Return TRUE to allow to export multiple properties.
   */
  protected function allowExportMultipleProperties(FieldDefinitionInterface $field_definition) {
    $properties = $this->getPropertyExportOptions($field_definition);
    return (bool) (count($properties) > 1);
  }

  /**
   * {@inheritdoc}
   */
  public function import(ContentEntityInterface $entity, $field_definition, $property_name = '', $options = []) {
    // May be one day we could do the reverse and import values from the csv.
    // Currently, this is just a placeholder. No code yet. Any help is welcomed.
  }

}
