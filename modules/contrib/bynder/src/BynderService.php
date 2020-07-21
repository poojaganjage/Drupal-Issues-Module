<?php

namespace Drupal\bynder;

use Drupal\bynder\Plugin\Field\FieldType\BynderMetadataItem;
use Drupal\bynder\Plugin\media\Source\Bynder;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\StateInterface;
use GuzzleHttp\Exception\ClientException;

/**
 * Bynder service.
 */
class BynderService implements BynderServiceInterface {

  /**
   * The state metadata update ID key.
   */
  const METADATA_UPDATE_ID_KEY = 'bynder.metadata_update_id';

  /**
   * The state metadata timestamp key.
   */
  const METADATA_UPDATE_TIMESTAMP_KEY = 'bynder.metadata_update_timestamp';

  /**
   * The default maximum number of items to process in one run.
   */
  const MAX_ITEMS = 100;

  /**
   * The Bynder API.
   *
   * @var \Drupal\bynder\BynderApi
   */
  protected $bynderApi;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The media storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $mediaStorage;

  /**
   * BynderMetadataService constructor.
   *
   * @param \Drupal\bynder\BynderApiInterface $bynder_api
   *   The Bynder API service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Logger factory.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   */
  public function __construct(BynderApiInterface $bynder_api, EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_factory, StateInterface $state, TimeInterface $time, ConfigFactoryInterface $config_factory) {
    $this->bynderApi = $bynder_api;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger_factory->get('bynder');
    $this->state = $state;
    $this->time = $time;
    $this->configFactory = $config_factory;
    $this->mediaStorage = $entity_type_manager->getStorage('media');
  }

  /**
   * {@inheritdoc}
   */
  public function getBynderMediaTypes() {
    $bynder_media_types = [];
    /** @var \Drupal\media\MediaTypeInterface $media_type */
    foreach ($this->entityTypeManager->getStorage('media_type')->loadMultiple() as $media_type_id => $media_type) {
      if ($media_type->getSource() instanceof Bynder) {
        $bynder_media_types[$media_type_id] = $media_type;
      }
    }

    return $bynder_media_types;
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalCountOfMediaEntities() {
    $bynder_media_types = $this->getBynderMediaTypes();
    if (empty($bynder_media_types)) {
      return 0;
    }

    $count = $this->mediaStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition($this->mediaStorage->getEntityType()->getKey('bundle'), array_keys($bynder_media_types), 'IN')
      ->count()
      ->execute();

    return (int) $count;
  }

  /**
   * {@inheritdoc}
   */
  public function updateLocalMetadataCron() {
    // Get the update frequency value in seconds. In case it is empty or set to
    // zero, do not do any updates.
    $update_frequency = (int) $this->configFactory->get('bynder.settings')->get('update_frequency');
    if ($update_frequency === 0) {
      return;
    }

    // Only run updates if the last completed update was more than the
    // configured amount of time ago.
    $last_update = $this->state->get(static::METADATA_UPDATE_TIMESTAMP_KEY);
    $request_time = $this->time->getRequestTime();
    if ($last_update && $request_time - $last_update < $update_frequency) {
      return;
    }

    $results = $this->updateMetadataLastMediaEntities($this->state->get(static::METADATA_UPDATE_ID_KEY));

    // There are no Bynder media types, Bynder media entities to update or we
    // are processing the latest chunk.
    if (empty($results) || $results['total'] < static::MAX_ITEMS) {
      $this->state->set(static::METADATA_UPDATE_TIMESTAMP_KEY, $request_time);
      $this->state->delete(static::METADATA_UPDATE_ID_KEY);
      return;
    }

    // Update the maximum update ID with a new maximum ID.
    $this->state->set(static::METADATA_UPDATE_ID_KEY, $results['max_id']);
  }

  /**
   * {@inheritdoc}
   */
  public function updateMetadataLastMediaEntities($minimum_id = NULL, $limit = BynderService::MAX_ITEMS) {
    $bynder_media_types = $this->getBynderMediaTypes();
    if (empty($bynder_media_types)) {
      return [];
    }

    $entity_id_key = $this->mediaStorage->getEntityType()->getKey('id');

    // Get the Bynder media entity IDs.
    $query = $this->mediaStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition($this->mediaStorage->getEntityType()->getKey('bundle'), array_keys($bynder_media_types), 'IN')
      ->sort($entity_id_key)
      ->range(0, $limit);

    if ($minimum_id) {
      $query->condition($entity_id_key, $minimum_id, '>');
    }

    $media_ids = $query->execute();

    /** @var \Drupal\media\MediaInterface[] $media_entities */
    $media_entities = $this->mediaStorage->loadMultiple($media_ids);

    $bynder_media_entities = [];

    // Get the remote media UUIDs.
    foreach ($media_entities as $media_entity) {
      if ($remote_uuid = $media_entity->getSource()->getSourceFieldValue($media_entity)) {
        $bynder_media_entities[$remote_uuid] = $media_entity;
      }
    }

    // No media entities to process.
    if (empty($bynder_media_entities)) {
      return [];
    }

    // Get the most recent metadata for the queried IDs.
    $query = [
      'ids' => implode(',', array_keys($bynder_media_entities)),
    ];

    try {
      $media_list = $this->bynderApi->getMediaList($query);
    }
    catch (ClientException $e) {
      $this->logger->error($e->getMessage());
      return [];
    }

    $updated_entities = [];
    foreach ($media_list as $index => $item) {
      /** @var \Drupal\media\MediaInterface $media_entity */
      $media_entity = $bynder_media_entities[$item['id']];
      /** @var \Drupal\bynder\Plugin\media\Source\Bynder $source */
      $source = $media_entity->getSource();
      $remote_metadata = $source->filterRemoteMetadata($item);
      if ($source->hasMetadataChanged($media_entity, $remote_metadata)) {
        $media_entity->set(BynderMetadataItem::METADATA_FIELD_NAME, Json::encode($remote_metadata))->save();
      }
      $updated_entities[$media_entity->id()] = $media_entity;

      // Remove the processed item.
      unset($bynder_media_entities[$item['id']]);
    }

    $missing_remote_entities = [];
    // Log warning in case a media entity has been removed in the remote system.
    foreach ($bynder_media_entities as $missing_remote_entity) {
      $missing_remote_entities[$missing_remote_entity->id()] = $missing_remote_entity;

      $this->logger->warning('The media entity (ID: @id, Remote UUID: @remote_uuid) has been removed from the remote system.', [
        '@id' => $missing_remote_entity->id(),
        '@remote_uuid' => $missing_remote_entity->getSource()->getSourceFieldValue($missing_remote_entity),
      ]);
    }

    return [
      'updated' => $updated_entities,
      'skipped' => $missing_remote_entities,
      'total' => count($media_ids),
      'max_id' => max($media_ids),
    ];
  }

}
