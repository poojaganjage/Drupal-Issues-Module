<?php

namespace Drupal\json_template\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\OptionsProviderInterface;
use Drupal\Core\Field\FieldItemBase;

/**
 * Plugin implementation of the 'string with template' field type.
 *
 * @FieldType(
 *   id = "list_template",
 *   label = @Translation("JSON templates list"),
 *   description = @Translation("Field containing id of a JSON template."),
 *   category = @Translation("JSON Template"),
 *   default_widget = "options_select",
 *   default_formatter = "string"
 * )
 */
class ListTemplateItem extends FieldItemBase implements OptionsProviderInterface {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [
      'value' => DataDefinition::create('string')
        ->setLabel(t('Template'))
        ->addConstraint('Length', ['max' => 255])
        ->setRequired(TRUE)
        ->setDescription(t('Frontend template to use with this field')),
    ];
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => 255,
        ],
      ],
      'indexes' => [
        'value' => ['value'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {
    return $this->getSettableOptions($account);
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleValues(AccountInterface $account = NULL) {
    return $this->getSettableValues($account);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableOptions(AccountInterface $account = NULL) {
    $options = [];
    // @TODO When https://www.drupal.org/project/drupal/issues/2914419 is fixed,
    // get this service with DI.
    $definitions = \Drupal::service('plugin.manager.json_template.template')->getDefinitionsForId($this->getSetting('available_for'));
    foreach ($definitions as $key => $definition) {
      $options[$key] = $definition['title'];
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableValues(AccountInterface $account = NULL) {
    return array_keys(\Drupal::service('plugin.manager.json_template.template')->getDefinitionsForId($this->getSetting('available_for')));
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    return [
      'available_for' => [
        '#type' => 'textfield',
        '#title' => $this->t('Identifier for availability of template'),
        '#required' => FALSE,
        '#default_value' => $this->getSetting('available_for'),
        '#disabled' => $has_data,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return ['available_for' => ''];
  }

}
