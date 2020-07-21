<?php

namespace Drupal\bynder\Plugin\Action;

use Drupal\bynder\Plugin\Field\FieldType\BynderMetadataItem;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\Plugin\Action\EntityActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Updates the Bynder metadata.
 *
 * @Action(
 *   id = "bynder_metadata",
 *   action_label = @Translation("Update Bynder metadata"),
 *   type = "media"
 * )
 */
class UpdateMetadataAction extends EntityActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->getSource()->ensureMetadata($entity, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if (!$object->hasField(BynderMetadataItem::METADATA_FIELD_NAME) || $object->get(BynderMetadataItem::METADATA_FIELD_NAME)->getFieldDefinition()->getType() !== 'bynder_metadata') {
      return $return_as_object ? AccessResult::forbidden() : FALSE;
    }

    return $return_as_object ? AccessResult::allowed() : TRUE;
  }

}
