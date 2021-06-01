<?php

namespace Drupal\json_template\Plugin;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Manages discovery and instantiation of json template plugins.
 *
 * JSON templates are plugins that allow for supplying javascript templates
 * to frontend for rendering some pieces of javascript-generated content. This
 * is useful when some module implements front-end processing and rendering of
 * data, retrieved, eg, from some external service, and Drupal admin backend
 * wants to provide selection of templates to render these data.
 */
interface JsonTemplateManagerInterface extends PluginManagerInterface {

  /**
   * Get plugin definitions available for certain id.
   *
   * @param string $id
   *   Arbitrary id for which use of this template is allowed (eg, module name
   *   or block id).
   *
   * @return array
   *   Array of definition arrays, keyed by definition id.
   */
  public function getDefinitionsForId(string $id);

}
