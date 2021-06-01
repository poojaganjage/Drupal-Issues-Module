<?php

namespace Drupal\important_information\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the II Content type entity.
 *
 * @ConfigEntityType(
 *   id = "ii_content_type",
 *   label = @Translation("II Content Type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\important_information\IIContentTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\important_information\Form\IIContentTypeForm",
 *       "edit" = "Drupal\important_information\Form\IIContentTypeForm",
 *       "delete" = "Drupal\important_information\Form\IIContentTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\important_information\IIContentTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "ii_content_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "ii_content",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/ii_content_type/{ii_content_type}",
 *     "add-form" = "/admin/structure/ii_content_type/add",
 *     "edit-form" = "/admin/structure/ii_content_type/{ii_content_type}/edit",
 *     "delete-form" = "/admin/structure/ii_content_type/{ii_content_type}/delete",
 *     "collection" = "/admin/structure/ii_content_type"
 *   }
 * )
 */
class IIContentType extends ConfigEntityBundleBase implements IIContentTypeInterface {

  /**
   * The II Content type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The II Content type label.
   *
   * @var string
   */
  protected $label;

}
