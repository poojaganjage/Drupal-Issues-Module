<?php

namespace Drupal\entity_export_csv\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\entity_export_csv\EntityExportCsvManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define entity export csv settings form.
 */
class EntityExportCsvSettings extends ConfigFormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Entity\EntityTypeBundleInfoInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Drupal\Core\Entity\EntityFieldManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity export csv manager.
   *
   * @var \Drupal\entity_export_csv\EntityExportCsvManagerInterface
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityFieldManagerInterface $entity_field_manager, EntityExportCsvManagerInterface $entity_export_csv_manager) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityFieldManager = $entity_field_manager;
    $this->manager = $entity_export_csv_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_field.manager'),
      $container->get('entity_export_csv.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_export_csv_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'entity_export_csv.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $private_system_file = PrivateStream::basePath();
    if (!$private_system_file) {
      $this->messenger()->addWarning($this->t('The private system file is not configured. It is highly recommended to configure it. If not available, exports will use the temporary system file.'));
    }

    $form['entity_types'] = [
      '#type' => 'fieldset',
      "#title" => $this->t('Entity types'),
      '#description' => $this->t('Enable the entity types on which you want allow users be able to export them.'),
      '#tree' => TRUE,
    ];

    $entity_types = $this->manager->getSupportedContentEntityTypes(TRUE);
    // We do not use here a checkboxes to be able later to
    // enable / disable per bundle too.
    /** @var \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type */
    foreach ($entity_types as $entity_type_id => $entity_type) {
      $form['entity_types'][$entity_type_id] = [
        '#type' => 'container',
        '#tree' => TRUE,
        '#prefix' => '<div id="' . $entity_type_id .'">',
        '#suffix' => '</div>',
      ];

      $form['entity_types'][$entity_type_id]['enable'] = [
        '#type' => 'checkbox',
        '#title' => $entity_type->getLabel(),
        '#default_value' => $config->get('entity_types.' . $entity_type_id . '.enable'),
      ];
      $bundles_entity_type = $this->manager->getBundlesPerEntityType($entity_type_id, TRUE);
      $default_bundles = $config->get('entity_types.' . $entity_type_id . '.limit_per_bundle') ?: [];
      $default_bundles = array_filter($default_bundles);
      $bundles = $this->getElementPropertyValue(['entity_types', $entity_type_id, 'limit_per_bundle'], $form_state, $default_bundles);
      $bundles = array_filter($bundles);
      $form['entity_types'][$entity_type_id]['limit_per_bundle'] = [
        '#type' => 'checkboxes',
        "#title" => $this->t('Limit bundles for @entity_type', ['@entity_type' => $entity_type->getLabel()]),
        "#description" => $this->t('Leave empty to select all.'),
        '#options' => $bundles_entity_type,
        '#default_value' => $bundles,
        '#attributes' => [
          'class' => [
            'inline-checkboxes',
          ],
        ],
        '#states' => [
          'visible' => [
            ':input[name="entity_types[' . $entity_type_id .'][enable]"]' => ['checked' => TRUE],
          ],
        ],
        '#ajax' => [
          'event' => 'change',
          'method' => 'replace',
          'wrapper' => 'bundles-' . $entity_type_id,
          'callback' => [$this, 'ajaxReplaceEntityTypeBundlesCallback'],
        ],
      ];

      $form['entity_types'][$entity_type_id]['bundles'] = [
        '#type' => 'container',
        '#prefix' => '<div id="bundles-' . $entity_type_id .'">',
        '#suffix' => '</div>',
        '#tree' => TRUE,
      ];

      if (!empty($bundles)) {
        foreach ($bundles as $bundle) {
          $bundle_default_fields = $config->get('entity_types.' . $entity_type_id . '.bundles.' . $bundle) ?: [];
          $bundle_default_fields_value = $this->getElementPropertyValue(['entity_types', $entity_type_id, 'bundles', $bundle, 'wrapper'], $form_state, $bundle_default_fields);

          $form['entity_types'][$entity_type_id]['bundles'][$bundle] = [
            '#type' => 'details',
            '#title' => $this->t('Fields for bundle @label (machine name: @bundle)', ['@label' => $bundles_entity_type[$bundle], '@bundle' => $bundle]),
            '#description' => $this->t('Select the fields you want to be exportable. Leave empty to select all.'),
            "#open" => (bool) !empty(array_filter($bundle_default_fields_value)),
          ];

          $form['entity_types'][$entity_type_id]['bundles'][$bundle]['wrapper'] = [
            '#type' => 'checkboxes',
            "#title" => $this->t('Fields'),
            '#options' => $this->manager->getBundleFields($entity_type_id, $bundle),
            '#default_value' => $bundle_default_fields_value,
            '#attributes' => [
              'class' => [
                'inline-checkboxes',
              ],
            ],
          ];

        }
      }

    }

    $form['multiple'] = [
      '#type' => 'fieldset',
      "#title" => $this->t('Multiple fields'),
      '#tree' => TRUE,
    ];

    $form['multiple']['columns'] = [
      '#type' => 'select',
      "#title" => $this->t('Number of columns'),
      '#options' => $this->getColumnsOptions(),
      '#default_value' => $config->get('multiple.columns'),
      '#description' => $this->t('Select the maximum number of columns a user can select to export a multiple field into several columns.'),
      '#required' => TRUE,
    ];

    $form['#attached']['library'][] = 'entity_export_csv/admin';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues();
    $this->massageValues($values);

    $this->getConfiguration()
      ->setData($values)
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Massage the values.
   *
   * @param array $values
   */
  protected function massageValues(&$values) {
    foreach ($values['entity_types'] as $entity_type_id => $entity_type_value) {

      if (!empty($entity_type_value['limit_per_bundle'])) {
        $limit_per_bundle = $entity_type_value['limit_per_bundle'];
        $values['entity_types'][$entity_type_id]['limit_per_bundle'] = array_filter($limit_per_bundle);
      }

      if (!empty($entity_type_value['bundles'])) {
        foreach ($entity_type_value['bundles'] as $bundle => $bundle_value) {
          $wrapper = $bundle_value['wrapper'];
          unset($values['entity_types'][$entity_type_id]['bundles'][$bundle]['wrapper']);
          $values['entity_types'][$entity_type_id]['bundles'][$bundle] = array_filter($wrapper);
        }
      }
    }
  }

  /**
   * Ajax replace callback.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function ajaxReplaceEntityTypeBundlesCallback(array &$form, FormStateInterface &$form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $entity_type_id = $triggering_element['#parents'][1];
    return $form['entity_types'][$entity_type_id]['bundles'];
  }

  /**
   * Get element property value.
   *
   * @param $property
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param mixed $default
   *
   * @return array|mixed|null
   */
  protected function getElementPropertyValue($property, FormStateInterface $form_state, $default = '') {
    $test = $form_state->hasValue($property) ? $form_state->getValue($property) : '';
    return $form_state->hasValue($property)
      ? $form_state->getValue($property)
      : $default;
  }

  /**
   * Get the columns options.
   *
   * @return array
   *   An array of columns options.
   */
  protected function getColumnsOptions() {
    $options = [
      '1' => '1 column',
      '2' => '2 columns',
      '3' => '3 columns',
      '4' => '4 columns',
      '5' => '5 columns',
      '6' => '6 columns',
      '7' => '7 columns',
      '8' => '8 columns',
      '9' => '9 columns',
      '10' => '10 columns',
    ];
    return $options;
  }

  /**
   * Get configuration object.
   *
   * @return \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected function getConfiguration() {
    return $this->config('entity_export_csv.settings');
  }

}
