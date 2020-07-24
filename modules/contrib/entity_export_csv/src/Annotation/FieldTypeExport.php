<?php

namespace Drupal\entity_export_csv\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Field type export item annotation object.
 *
 * @see \Drupal\entity_export_csv\Plugin\FieldTypeExportManager
 * @see plugin_api
 *
 * @Annotation
 */
class FieldTypeExport extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The weight of the plugin.
   *
   * @var integer
   */
  public $weight = 0;

  /**
   * The plugin description.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The field types on which the plugin apply.
   *
   * @var array
   */
  public $field_type = [];

  /**
   * The entity type ids on which the plugin apply.
   *
   * @var array
   */
  public $entity_type = [];

  /**
   * The bundles on which the plugin apply.
   *
   * @var array
   */
  public $bundle = [];

  /**
   * The field name on which the plugin apply.
   *
   * @var array
   */
  public $field_name = [];

  /**
   * The first exclusive plugin found win and is the only one available.
   *
   * @var bool
   */
  public $exclusive = FALSE;

}
