<?php

namespace Drupal\bynder\Plugin\media\Source;

use Drupal\bynder\BynderApiInterface;
use Drupal\bynder\Plugin\Field\FieldType\BynderMetadataItem;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;
use Drupal\media\MediaTypeInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides media source plugin for Bynder.
 *
 * @MediaSource(
 *   id = "bynder",
 *   label = @Translation("Bynder"),
 *   description = @Translation("Provides business logic and metadata for Bynder."),
 *   default_thumbnail_filename = "bynder-logo.png",
 *   allowed_field_types = {"string", "string_long"}
 * )
 */
class Bynder extends MediaSourceBase {

  /**
   * Bynder api service.
   *
   * @var \Drupal\bynder\BynderApiInterface
   *   Bynder api service.
   */
  protected $bynderApi;

  /**
   * Account proxy.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $accountProxy;

  /**
   * The url generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Statically cached metadata information for the given assets.
   *
   * @var array
   */
  protected $metadata;

  /**
   * The logger factory service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The cache service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Used to track if an API request returned a timout error.
   *
   * @var bool
   */
  protected static $timoutDetected = FALSE;

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Entity field manager service.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\bynder\BynderApiInterface $bynder_api_service
   *   Bynder api service.
   * @param \Drupal\Core\Session\AccountProxyInterface $account_proxy
   *   Account proxy.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger factory service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, FieldTypePluginManagerInterface $field_type_manager, ConfigFactoryInterface $config_factory, BynderApiInterface $bynder_api_service, AccountProxyInterface $account_proxy, UrlGeneratorInterface $url_generator, LoggerChannelFactoryInterface $logger, CacheBackendInterface $cache, TimeInterface $time, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $field_type_manager, $config_factory);

    $this->bynderApi = $bynder_api_service;
    $this->accountProxy = $account_proxy;
    $this->urlGenerator = $url_generator;
    $this->logger = $logger;
    $this->cache = $cache;
    $this->time = $time;
    $this->moduleHandler = $module_handler;
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
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('config.factory'),
      $container->get('bynder_api'),
      $container->get('current_user'),
      $container->get('url_generator'),
      $container->get('logger.factory'),
      $container->get('cache.data'),
      $container->get('datetime.time'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    $fields = [
      'uuid' => $this->t('ID'),
      'name' => $this->t('Name'),
      'description' => $this->t('Description'),
      'tags' => $this->t('Tags'),
      'type' => $this->t('Type'),
      'video_preview_urls' => $this->t('Video preview urls'),
      'thumbnail_urls' => $this->t('Thumbnail urls'),
      'width' => $this->t('Width'),
      'height' => $this->t('Height'),
      'created' => $this->t('Date created'),
      'modified' => $this->t('Data modified'),
      'propertyOptions' => $this->t('Meta-property option IDs'),
    ];

    return $fields;
  }

  /**
   * Returns a list of remote metadata properties.
   *
   * @return array
   *   A list of remote metadata properties.
   */
  public function getRemoteMetadataProperties() {
    return [
      'id',
      'name',
      'description',
      'type',
      'videoPreviewURLs',
      'thumbnails',
      'original',
      'width',
      'height',
      'dateCreated',
      'dateModified',
      'propertyOptions',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'source_field' => '',
    ];
  }

  /**
   * Ensures the given media entity has Bynder metadata information in place.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media entity.
   * @param bool $force
   *   (optional) By default, this will not attempt to check for updated
   *   metadata if there is local data available. Pass TRUE to always check for
   *   changed metadata.
   *
   * @return bool
   *   TRUE if the metadata is ensured. Otherwise, FALSE.
   */
  public function ensureMetadata(MediaInterface $media, $force = FALSE) {
    $media_uuid = $this->getSourceFieldValue($media);
    if (!empty($this->metadata[$media_uuid]) && !$force) {
      return TRUE;
    }

    if (!$media->hasField(BynderMetadataItem::METADATA_FIELD_NAME)) {
      $this->logger->get('bynder')->error('The media type @type must have a Bynder metadata field named "bynder_metadata".', [
        '@type' => $media->bundle(),
      ]);
      return FALSE;
    }

    if (!$media->get(BynderMetadataItem::METADATA_FIELD_NAME)->isEmpty() && !$force) {
      $metadata = Json::decode($media->get(BynderMetadataItem::METADATA_FIELD_NAME)->value);
      if (is_array($metadata)) {
        $this->metadata[$media_uuid] = $metadata;
        return TRUE;
      }
    }

    try {
      // Try to fetch data if no previous request failed with a timeout.
      if (!static::$timoutDetected || $force) {
        $media_uuid = $this->getSourceFieldValue($media);
        $this->metadata[$media_uuid] = $this->filterRemoteMetadata((array) $this->bynderApi->getMediaInfo($media_uuid));

        if ($this->hasMetadataChanged($media, $this->metadata[$media_uuid])) {
          $encoded_metadata = Json::encode($this->metadata[$media_uuid]);
          if (!$encoded_metadata) {
            $this->logger->get('bynder')->error('Unable to JSON encode the returned API response for the media UUID @uuid.', [
              '@uuid' => $media_uuid,
            ]);

            return FALSE;
          }

          $media->set(BynderMetadataItem::METADATA_FIELD_NAME, $encoded_metadata)->save();
          return TRUE;
        }

        return TRUE;
      }
    }
    catch (GuzzleException $e) {
      if ($e instanceof ConnectException) {
        $handler_context = $e->getHandlerContext();
        if (isset($handler_context['errno']) && $handler_context['errno'] == 28) {
          static::$timoutDetected = TRUE;
        }
      }

      $this->logger->get('bynder')->error('Unable to fetch info about the asset represented by media @name (@id) with message @message.', [
        '@name' => $media->label(),
        '@id' => $media->id(),
        '@message' => $e->getMessage(),
      ]);
    }

    return FALSE;
  }

  /**
   * Returns a list of filtered remote metadata properties.
   *
   * @param array $metadata
   *   The metadata items.
   *
   * @return array
   *   Filtered list of remote metadata properties.
   */
  public function filterRemoteMetadata(array $metadata) {
    return array_intersect_key($metadata, array_combine($this->getRemoteMetadataProperties(), $this->getRemoteMetadataProperties()));
  }

  /**
   * Compares the local metadata and the remote metadata in case it changed.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media entity.
   * @param array $remote_metadata
   *   The remote metadata.
   *
   * @return bool
   *   TRUE if the remote metadata has changed. Otherwise, FALSE.
   */
  public function hasMetadataChanged(MediaInterface $media, array $remote_metadata) {
    $remote_metadata = $this->filterRemoteMetadata($remote_metadata);
    if ($media->get(BynderMetadataItem::METADATA_FIELD_NAME)->isEmpty() && !empty($remote_metadata)) {
      return TRUE;
    }

    $local_metadata = (array) Json::decode((string) $media->get(BynderMetadataItem::METADATA_FIELD_NAME)->value);
    return $local_metadata !== $remote_metadata;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $name) {
    $remote_uuid = $this->getSourceFieldValue($media);
    if ($name == 'uuid') {
      return $remote_uuid;
    }

    // Ensure the metadata information are fetched.
    if ($this->ensureMetadata($media)) {
      switch ($name) {
        case 'video_preview_urls':
          return isset($this->metadata[$remote_uuid]['videoPreviewURLs']) ? $this->metadata[$remote_uuid]['videoPreviewURLs'] : FALSE;

        case 'thumbnail_urls':
          return isset($this->metadata[$remote_uuid]['thumbnails']) ? $this->metadata[$remote_uuid]['thumbnails'] : FALSE;

        case 'thumbnail_uri':
          if (!empty($this->metadata[$remote_uuid]['thumbnails']['webimage'])) {
            if ($this->useRemoteImages()) {
              return $this->metadata[$remote_uuid]['thumbnails']['webimage'];
            }
            elseif ($file = system_retrieve_file($this->metadata[$remote_uuid]['thumbnails']['webimage'], NULL, TRUE)) {
              return $file->getFileUri();
            }
          }
          return parent::getMetadata($media, 'thumbnail_uri');

        case 'created':
          return isset($this->metadata[$remote_uuid]['dateCreated']) ? $this->metadata[$remote_uuid]['dateCreated'] : FALSE;

        case 'modified':
          return isset($this->metadata[$remote_uuid]['dateModified']) ? $this->metadata[$remote_uuid]['dateModified'] : FALSE;

        case 'default_name':
          return isset($this->metadata[$remote_uuid]['name']) ? $this->metadata[$remote_uuid]['name'] : parent::getMetadata($media, 'default_name');

        default:
          return isset($this->metadata[$remote_uuid][$name]) ? $this->metadata[$remote_uuid][$name] : FALSE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Check the connection with bynder.
    try {
      $this->bynderApi->getBrands();
    }
    catch (\Exception $exception) {
      if ($this->accountProxy->hasPermission('administer bynder configuration')) {
        drupal_set_message($this->t('Connecting with Bynder failed. Check if the configuration is set properly <a href=":url">here</a>.', [
          ':url' => $this->urlGenerator->generateFromRoute('bynder.configuration_form'),
        ]), 'error');
      }
      else {
        drupal_set_message($this->t('Something went wrong with the Bynder connection. Please contact the site administrator.'), 'error');
      }
    }

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * Get the primary value stored in the source field.
   *
   * @todo This helper method was added to MediaSourceBase in 8.5.0 but we
   * replicate it here because we want to support 8.4.0 sites as well. This
   * method can be safely removed once there is no need to support 8.4 anymore,
   * and we ensure the core Media dependency is bumped to 8.5.0 at least.
   *
   * @param \Drupal\media\MediaInterface $media
   *   A media item.
   *
   * @return mixed
   *   The source value.
   *
   * @throws \RuntimeException
   *   If the source field for the media source is not defined.
   */
  public function getSourceFieldValue(MediaInterface $media) {
    $source_field = $this->configuration['source_field'];
    if (empty($source_field)) {
      throw new \RuntimeException('Source field for media source is not defined.');
    }

    /** @var \Drupal\Core\Field\FieldItemInterface $field_item */
    $field_item = $media->get($source_field)->first();
    return $field_item->{$field_item->mainPropertyName()};
  }

  /**
   * Creates the metadata field storage definition.
   *
   * @return \Drupal\field\FieldStorageConfigInterface
   *   The unsaved field storage definition.
   */
  public function createMetadataFieldStorage() {
    return $this->entityTypeManager->getStorage('field_storage_config')
      ->create([
        'entity_type' => 'media',
        'field_name' => BynderMetadataItem::METADATA_FIELD_NAME,
        'type' => 'bynder_metadata',
        'cardinality' => 1,
        'locked' => TRUE,
      ]);
  }

  /**
   * Creates the metadata field definition.
   *
   * @param \Drupal\media\MediaTypeInterface $type
   *   The media type.
   *
   * @return \Drupal\field\FieldConfigInterface
   *   The unsaved field definition. The field storage definition, if new,
   *   should also be unsaved.
   */
  public function createMetadataField(MediaTypeInterface $type) {
    return $this->entityTypeManager->getStorage('field_config')
      ->create([
        'entity_type' => 'media',
        'field_name' => BynderMetadataItem::METADATA_FIELD_NAME,
        'bundle' => $type->id(),
        'label' => 'Bynder Metadata',
        'translatable' => FALSE,
        'field_type' => 'bynder_metadata',
      ]);
  }

  /**
   * Checks if remote images should be used.
   *
   * This checks to see if the configuration for using remote images is enabled
   * and that the Remote Stream Wrapper module is still enabled.
   *
   * @return bool
   *   TRUE if remote images should be used, or FALSE otherwise.
   */
  protected function useRemoteImages() {
    return $this->configFactory->get('bynder.settings')->get('use_remote_images')
      || $this->moduleHandler->moduleExists('remote_stream_wrapper');
  }

}
