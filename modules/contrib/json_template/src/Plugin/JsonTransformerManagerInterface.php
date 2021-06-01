<?php


namespace Drupal\json_template\Plugin;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Manages discovery and instantiation of json template transformer plugins.
 *
 * To use a template provided by json template plugin, we need to apply a
 * transformer which transforms arbitrary JSON into HTML applying this template.
 * Transformer consists of some Javascript templating library (eg, handlebars),
 * syntax of which the template implements, and Javascript plugin (transformer)
 * that implements the library-specific rendering syntax within general
 * transform function.
 */
interface JsonTransformerManagerInterface extends PluginManagerInterface {

}
