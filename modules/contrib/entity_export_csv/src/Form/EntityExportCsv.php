<?php

namespace Drupal\entity_export_csv\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\entity_export_csv\EntityExportCsvBatch;
use Drupal\entity_export_csv\EntityExportCsvManagerInterface;
use Drupal\entity_export_csv\Plugin\FieldTypeExportManagerInterface;
use Drupal\user\UserDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define entity export csv form.
 */
class EntityExportCsv extends FormBase {

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\entity_export_csv\Plugin\FieldTypeExportManagerInterface
   */
  protected $fieldTypeExportManager;

  /**
   * The entity type bundle info service.
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
   * Drupal\user\UserDataInterface definition.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * The entity export csv manager.
   *
   * @var \Drupal\entity_export_csv\EntityExportCsvManagerInterface
   */
  protected $manager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Define entity export csv form constructor.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\entity_export_csv\Plugin\FieldTypeExportManagerInterface $field_type_export_manager
   *   The field type export manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   * @param \Drupal\entity_export_csv\EntityExportCsvManagerInterface $entity_export_csv_manager
   *   The entity export csv manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(RendererInterface $renderer, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, FieldTypeExportManagerInterface $field_type_export_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityFieldManagerInterface $entity_field_manager, UserDataInterface $user_data, EntityExportCsvManagerInterface $entity_export_csv_manager, LanguageManagerInterface $language_manager) {
    $this->renderer = $renderer;
    $this->setConfigFactory($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->fieldTypeExportManager = $field_type_export_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityFieldManager = $entity_field_manager;
    $this->userData = $user_data;
    $this->manager = $entity_export_csv_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('renderer'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.field_type_export'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_field.manager'),
      $container->get('user.data'),
      $container->get('entity_export_csv.manager'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_export_csv';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'entity_export_csv/export_form';
    $form['#attached']['library'][] = 'entity_export_csv/download';
    $options = $this->manager->getContentEntityTypesEnabled(TRUE);
    if (empty($options)) {
      $this->messenger()->addWarning(
        $this->t('No entity type have been configured to be exported.')
      );
      return [];
    }

    if ($this->languageManager->isMultilingual()) {
      $languages = $this->languageManager->getLanguages();
      $languages_options = [];
      foreach ($languages as $language_id => $language) {
        $languages_options[$language->getId()] = $language->getName();
      }
      $form['langcode'] = [
        '#type' => 'select',
        '#title' => $this->t('Language'),
        '#description' => $this->t('Select the language you want export'),
        '#options' => $languages_options,
        '#default_vlue' => $this->languageManager->getDefaultLanguage()->getId(),
      ];
    }


    $user_data = $this->userData->get('entity_export_csv', $this->currentUser()->id(), 'entity_export_csv') ?: [];
    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity type'),
      '#required' => TRUE,
      '#options' => $options,
      '#ajax' => [
        'event' => 'change',
        'method' => 'replace',
        'wrapper' => 'bundle-wrapper',
        'callback' => [$this, 'ajaxReplaceBundleCallback'],
      ],
    ];

    $form['bundle_wrapper'] = [
      '#type' => 'container',
      '#prefix' => '<div id="bundle-wrapper">',
      '#suffix' => '</div>',
    ];

    $entity_type_id = $this->getElementPropertyValue('entity_type', $form_state);
    $bundles = $this->manager->getBundlesEnabledPerEntityType($entity_type_id, TRUE);
    $bundles_clone = $bundles;
    $bundles_clone = array_keys($bundles_clone);
    $default_bundle = reset($bundles_clone);
    if ($entity_type_id) {
      $form['bundle_wrapper']['bundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Bundle'),
        '#description' => $this->t('Select the bundle to export.'),
        '#options' => $bundles,
        '#default_value' => $this->getElementPropertyValue('bundle', $form_state, $default_bundle),
        '#required' => TRUE,
        '#ajax' => [
          'event' => 'change',
          'method' => 'replace',
          'wrapper' => 'fields-wrapper',
          'callback' => [$this, 'ajaxReplaceFieldsCallback'],
        ],
      ];

      $form['bundle_wrapper']['fields'] = [
        '#type' => 'container',
        '#prefix' => '<div id="fields-wrapper">',
        '#suffix' => '</div>',
        '#tree' => TRUE,
        '#attributes' => [
          'class' => [
            'inline-elements',
          ],
        ],
      ];

      $bundle = $this->getElementPropertyValue('bundle', $form_state, $default_bundle);
      if ($bundle) {
        $form['bundle_wrapper']['fields']['#title'] = $this->t('Select fields to export');
        $fields = $this->manager->getBundleFieldsEnabled($entity_type_id, $bundle, TRUE);
        $user_default_values = [];
        if (!empty($user_data[$entity_type_id][$bundle])) {
          $user_default_values = $user_data[$entity_type_id][$bundle];
        }
        if (!empty($user_default_values)) {
          $this->manager->sortNaturalFields($fields, $user_default_values);
        }
        foreach ($fields as $field_name => $definition) {
          $field_name_class = Html::cleanCssIdentifier($field_name);

          $form['bundle_wrapper']['fields'][$field_name] = [
            '#type' => 'fieldset',
            '#title' => $definition->getLabel(),
            '#tree' => TRUE,
          ];

          $enable_default = isset($user_default_values[$field_name]['enable']) ? $user_default_values[$field_name]['enable'] : TRUE;
          $form['bundle_wrapper']['fields'][$field_name]['enable'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Enable'),
            '#default_value' => $this->getElementPropertyValue(['fields', $field_name, 'enable'], $form_state, $enable_default),
          ];
          $order_default = isset($user_default_values[$field_name]['order']) ? $user_default_values[$field_name]['order'] : 0;
          $form['bundle_wrapper']['fields'][$field_name]['order'] = [
            '#type' => 'number',
            '#title' => $this->t('Order'),
            '#required' => TRUE,
            '#default_value' => $this->getElementPropertyValue(['fields', $field_name, 'order'], $form_state, $order_default),
          ];

          $field_type = $definition->getType();
          $exporters = $this->fieldTypeExportManager->getFieldTypeOptions($field_type, $entity_type_id, $bundle, $field_name);
          $exporter_ids = array_keys($exporters);
          $default_exporter = (isset($user_default_values[$field_name]['exporter']) && isset($exporters[$user_default_values[$field_name]['exporter']])) ? $user_default_values[$field_name]['exporter'] : $this->getDefaultExporterId($exporter_ids);
          $default_exporter_value = $this->getElementPropertyValue(['fields', $field_name, 'exporter'], $form_state, $default_exporter);
          $form['bundle_wrapper']['fields'][$field_name]['exporter'] = [
            '#type' => 'select',
            '#title' => $this->t('Export format'),
            '#options' => $exporters,
            '#default_value' => $default_exporter_value,
            '#required' => TRUE,
            '#ajax' => [
              'event' => 'change',
              'method' => 'replace',
              'wrapper' => 'fields-wrapper',
              'callback' => [$this, 'ajaxReplaceFieldsCallback'],
            ],
          ];

          $form['bundle_wrapper']['fields'][$field_name]['form'] = [
            '#type' => 'container',
            '#prefix' => '<div id="export-form-wrapper-"' . $field_name_class . '>',
            '#suffix' => '</div>',
          ];
          $triggering = $form_state->getTriggeringElement();
          if ($triggering['#name'] === 'fields[' . $field_name . '][exporter]') {
            $default_exporter_value = $triggering['#value'];
          }
          // @TODO handle configuration values
          $configuration_default = isset($user_default_values[$field_name]['form']['options']) ? $user_default_values[$field_name]['form']['options'] : [];
          $configuration = $this->getElementPropertyValue(['fields', $field_name, 'form', 'options'], $form_state, $configuration_default);
          /** @var \Drupal\entity_export_csv\Plugin\FieldTypeExportInterface $plugin */
          $plugin = $this->fieldTypeExportManager->createInstance($default_exporter_value, $configuration);
          $form['bundle_wrapper']['fields'][$field_name]['form']['options'] = $plugin->buildConfigurationForm([], $form_state, $definition);
        }

        $save_default = TRUE;
        $form['bundle_wrapper']['fields']['save'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Save this settings for this bundle (leave unchecked will delete any settings saved).'),
          '#default_value' => $this->getElementPropertyValue(['fields', 'save'], $form_state, $save_default),
          '#wrapper_attributes' => [
            'class' => [
              'reset-flex',
            ],
          ],
          '#states' => [
            'invisible' => [
              ':input[name="entity_type"]' => ['value' => ''],
            ],
          ],
        ];

      }

    }

    $form['actions']['#type'] = 'actions';

    $form['actions']['export'] = [
      '#type' => 'submit',
      '#name' => 'export',
      '#value' => $this->t('Export'),
      '#states' => [
        'invisible' => [
          ':input[name="entity_type"]' => ['value' => ''],
        ],
      ],
    ];

    $form['actions']['save'] = [
      '#type' => 'submit',
      '#name' => 'save',
      '#value' => $this->t('Save settings'),
      '#attributes' => [
        'class' => [
          'btn-secondary',
        ],
      ],
      '#states' => [
        'invisible' => [
          [':input[name="fields[save]"]' => ['checked' => FALSE]],
          'OR',
          [':input[name="entity_type"]' => ['value' => '']],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues();
    if (!isset($values['entity_type']) || !isset($values['bundle'])) {
      return;
    }
    $save = $values['fields']['save'];
    unset($values['fields']['save']);

    $entity_type_id = $values['entity_type'];
    $bundle_id = $values['bundle'];
    $fields = $values['fields'];

    $user_data = $this->userData->get('entity_export_csv', $this->currentUser()->id(), 'entity_export_csv') ?: [];
    if (!empty($save)) {
      $user_data[$entity_type_id][$bundle_id] = $fields;
    }
    else {
      if (isset($user_data[$entity_type_id][$bundle_id])) {
        unset($user_data[$entity_type_id][$bundle_id]);
      }
    }
    $this->userData->set('entity_export_csv', $this->currentUser()->id(), 'entity_export_csv', $user_data);
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element['#name'] === 'save') {
      $this->messenger()->addStatus($this->t('Settings successfully updated.'));
      return;
    }
    $langcode = isset($values['langcode']) ? $values['langcode'] : NULL;
    $entity_types = $this->manager->getContentEntityTypesEnabled(TRUE);
    $bundles = $this->manager->getBundlesEnabledPerEntityType($entity_type_id, TRUE);
    $batch = [
      'title' => $this->t('Exporting @entity_type of type @bundle', [
        '@bundle' => $bundles[$bundle_id],
        '@entity_type' => $entity_types[$entity_type_id],
      ]),
      'operations' => [
        [
          '\Drupal\entity_export_csv\EntityExportCsvBatch::export',
          [$entity_type_id, $bundle_id, $fields, $langcode]
        ],
      ],
      'finished' => '\Drupal\entity_export_csv\EntityExportCsvBatch::finished',
    ];
    batch_set($batch);
  }

  protected function massageValues(&$values) {
    if ($values) {

    }
  }

  /**
   * Get the default exporter id for a field type.
   *
   * @param array $exporter_ids
   *
   * @return string
   *   The default exporter id.
   */
  protected function getDefaultExporterId(array $exporter_ids) {
    $default_exporter = reset($exporter_ids);
    return $default_exporter;
  }

  /**
   * Ajax replace callback.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function ajaxReplaceBundleCallback(array $form, FormStateInterface $form_state) {
    return $form['bundle_wrapper'];
  }

  /**
   * Ajax replace callback.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function ajaxReplaceFieldsCallback(array $form, FormStateInterface $form_state) {
    return $form['bundle_wrapper']['fields'];
  }

  /**
   * Ajax replace callback.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function ajaxReplaceExporterCallback(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parent = $triggering_element['#parents'][0];
    return $form['bundle_wrapper']['fields'][$parent]['form'];
  }

  /**
   * Ajax replace callback.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function ajaxReplaceCallback(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Get entity content export settings.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The configuration instance.
   */
  protected function getConfiguration() {
    return $this->configFactory->get('entity_export_csv.settings');
  }

  /**
   * Get element property value.
   *
   * @param array $property
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param mixed $default
   *
   * @return array|mixed|null
   */
  protected function getElementPropertyValue($property, FormStateInterface $form_state, $default = '') {
    return $form_state->hasValue($property)
      ? $form_state->getValue($property)
      : $default;
  }

}
