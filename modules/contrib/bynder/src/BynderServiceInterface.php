<?php

namespace Drupal\bynder;

/**
 * Provides methods to manage the Bynder module.
 */
interface BynderServiceInterface {

  /**
   * Updates the cron metadata information of the local media entities.
   *
   * Limits the number of processed items to BynderService::MAX_ITEMS.
   */
  public function updateLocalMetadataCron();

  /**
   * Returns the Bynder media types.
   *
   * @return \Drupal\media\MediaTypeInterface[]
   *   A list of Bynder media types.
   */
  public function getBynderMediaTypes();

  /**
   * Returns the total number of Bynder media entities.
   *
   * @return int
   *   A total number of Bynder media entities.
   */
  public function getTotalCountOfMediaEntities();

  /**
   * Updates metadata of the next N media entities starting at the minimum ID.
   *
   * @param string|null $minimum_id
   *   (optional) The minimum media entity ID to query items for.
   * @param int $limit
   *   (optional) The number of items to update per run.
   *
   * @return array
   *   Empty array if updates are not possible. Otherwise, array with the keys:
   *     - updated (a list of updated media entities keyed by the media ID)
   *     - skipped (a list of skipped media entities keyed by the media ID)
   *     - total (the total count of processed media entities)
   *     - max_id (the maximum media entity ID that was last processed)
   */
  public function updateMetadataLastMediaEntities($minimum_id = NULL, $limit = BynderService::MAX_ITEMS);

}
