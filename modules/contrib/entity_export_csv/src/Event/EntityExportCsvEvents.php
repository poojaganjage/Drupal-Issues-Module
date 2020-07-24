<?php

namespace Drupal\entity_export_csv\Event;

/**
 * Define the event names for entity export csv.
 */
final class EntityExportCsvEvents {

  /**
   * Name of the event fired before returning the fields supported per entity
   * type and/or bundle.
   *
   * @Event
   *
   * @see \Drupal\entity_export_csv\Event\EntityExportCsvFieldsSupportedEvent
   */
  const ENTITY_EXPORT_CSV_FIELDS_SUPPORTED = 'entity_export_csv.fields_supported';

  /**
   * Name of the event fired before returning the fields enabled per entity
   * type and/or bundle.
   *
   * @Event
   *
   * @see \Drupal\entity_export_csv\Event\EntityExportCsvFieldsEnabledEvent
   */
  const ENTITY_EXPORT_CSV_FIELDS_ENABLE = 'entity_export_csv.fields_enable';

}
