<?php

namespace Drupal\entity_export_csv;

use Drupal\Component\Datetime\Time;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_activity\Entity\SubscriptionInterface;
use Drupal\entity_activity\Event\EntityActivityEvent;
use Drupal\entity_activity\Event\EntityActivityEvents;
use Drupal\entity_activity\Event\EntityActivitySupportEntityTypeEvent;
use Drupal\entity_activity\Plugin\LogGeneratorInterface;
use Drupal\entity_activity\Plugin\LogGeneratorManagerInterface;
use Drupal\entity_export_csv\Event\EntityExportCsvEvents;
use Drupal\entity_export_csv\Event\EntityExportCsvFieldsEnabledEvent;
use Drupal\entity_export_csv\Event\EntityExportCsvFieldsEvent;
use Drupal\entity_export_csv\Event\EntityExportCsvFieldsSupportedEvent;
use Drupal\entity_export_csv\Plugin\FieldTypeExportManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class EntityExportCsvManager.
 */
class EntityExportCsvManager implements EntityExportCsvManagerInterface {

  use StringTranslationTrait;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The field type export manager.
   *
   * @var \Drupal\entity_export_csv\Plugin\FieldTypeExportManagerInterface
   */
  protected $manager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\Time
   */
  protected $time;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * EntityActivityManager constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\entity_export_csv\Plugin\FieldTypeExportManagerInterface $manager
   *   The field type export manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Queue\QueueFactory $queue
   *   The queue factory.
   * @param \Drupal\Component\Datetime\Time $time
   *   The time service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository, LanguageManagerInterface $language_manager, EntityFieldManagerInterface $entity_field_manager, FieldTypeExportManagerInterface $manager, AccountProxyInterface $current_user, EntityTypeBundleInfoInterface $entity_type_bundle_info, EventDispatcherInterface $event_dispatcher, QueueFactory $queue, Time $time, StateInterface $state) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
    $this->languageManager = $language_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->manager = $manager;
    $this->currentUser = $current_user;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->eventDispatcher = $event_dispatcher;
    $this->queueFactory = $queue;
    $this->time = $time;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedContentEntityTypes($return_object = TRUE) {
    /** @var \Drupal\Core\Entity\ContentEntityTypeInterface[] $entity_types */
    $entity_types = $this->entityTypeManager->getDefinitions();
    foreach ($entity_types as $entity_type_id => $entity_type) {
      if (!$entity_type instanceof ContentEntityTypeInterface) {
        unset($entity_types[$entity_type_id]);
        continue;
      }
    }
    if ($return_object) {
      return $entity_types;
    }
    else {
      return array_keys($entity_types);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getContentEntityTypesEnabled($return_label = FALSE) {
    $entity_types = [];
    $entity_type_settings = $this->configFactory->get('entity_export_csv.settings')->get('entity_types');
    foreach ($entity_type_settings as $entity_type_id => $value) {
      if ($value['enable']) {
        if ($return_label) {
          $label = $this->entityTypeManager->getStorage($entity_type_id)->getEntityType()->getLabel();
          $entity_types[$entity_type_id] = $label;
        }
        else {
          $entity_types[$entity_type_id] = $entity_type_id;
        }
      }
    }
    return $entity_types;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundlesPerEntityType($entity_type_id, $return_label = FALSE) {
    $options = [];
    $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
    foreach ($bundles as $id => $bundle) {
      if ($return_label) {
        $options[$id] = $bundle['label'];
      }
      else {
        $options[$id] = $id;
      }
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundlesEnabledPerEntityType($entity_type_id, $return_label = FALSE) {
    $options = $this->getBundlesPerEntityType($entity_type_id, $return_label);
    $entity_type_bundle_settings = $this->getConfiguration()->get('entity_types.' . $entity_type_id . '.limit_per_bundle') ?: [];
    if (!empty($entity_type_bundle_settings)) {
      $options = array_intersect_key($options, $entity_type_bundle_settings);
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleFieldDefinitions($entity_type_id, $bundle) {
    $options = [];
    $fields = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
    foreach ($fields as $field_name => $field_definition) {
      $options[$field_name] = $field_definition;
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleFields($entity_type_id, $bundle, $return_field_definition = FALSE) {
    $options = [];
    $fields = $this->getBundleFieldDefinitions($entity_type_id, $bundle);
    foreach ($fields as $field_name => $field_definition) {
      if ($return_field_definition) {
        $options[$field_name] = $field_definition;
      }
      else {
        $options[$field_name] = $field_definition->getLabel();
      }
    }
    $event = new EntityExportCsvFieldsSupportedEvent($options, $entity_type_id, $bundle, $return_field_definition);
    $this->eventDispatcher->dispatch(EntityExportCsvEvents::ENTITY_EXPORT_CSV_FIELDS_SUPPORTED, $event);
    $options = $event->getFields();
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleFieldsEnabled($entity_type_id, $bundle, $return_field_definition = FALSE) {
    $options = $this->getBundleFields($entity_type_id, $bundle, $return_field_definition);
    $bundle_fields_settings = $this->getConfiguration()->get('entity_types.' . $entity_type_id . '.bundles.' . $bundle ) ?: [];
    if (!empty($bundle_fields_settings)) {
      $options = array_intersect_key($options, $bundle_fields_settings);
    }
    $event = new EntityExportCsvFieldsEnabledEvent($options, $entity_type_id, $bundle, $return_field_definition);
    $this->eventDispatcher->dispatch(EntityExportCsvEvents::ENTITY_EXPORT_CSV_FIELDS_ENABLE, $event);
    $options = $event->getFields();
    return $options;
  }

  /**
   * Get configuration object.
   *
   * @return \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected function getConfiguration() {
    return $this->configFactory->get('entity_export_csv.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function sortNaturalFields(&$fields, $default_values) {
    $index = 0;
    foreach ($fields as $field_name => &$item) {
      $item = [$index++, $field_name, $item];
    }

    uasort($fields, function($a, $b) use($default_values) {
      if (isset($default_values[$a[1]]['order'], $default_values[$b[1]]['order'])) {
        if ($default_values[$a[1]]['order'] != $default_values[$b[1]]['order']) {
          return $default_values[$a[1]]['order'] < $default_values[$b[1]]['order'] ? -1 : 1;
        }
        else {
          return $a[0] < $b[0] ? -1 : 1;
        }
      }
      else {
        return $a[0] < $b[0] ? -1 : 1;
      }
    });

    foreach ($fields as &$item) {
      $item = $item[2];
    }
  }

}
