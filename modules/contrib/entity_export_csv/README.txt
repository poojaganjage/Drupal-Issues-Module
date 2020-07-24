CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Similar modules
 * Installation
 * Configuration
 * Usage
 * Extending Entity Export CSV
 * Resources
 * Troubleshooting
 * FAQ
 * Maintainers

INTRODUCTION
------------

The Entity Export CSV module allows:

  * To export any content entity from Drupal 8
  * To select which fields are exportable for each entity and each bundle
  * To configure how each field of an entity is exported
  * To configure field by field their export behavior when multiple fields are
    involved (export in a single column with separator and export of each value
    in a separate column)

The module is easily customizable for the export of a particular field, with a
specific business need. It is designed to be usable by an end user without
special administrative rights on the configuration of a Drupal 8 project
(Views, Entity View Mode, etc.).

REQUIREMENTS
------------
None.

SIMILAR MODULES
------------

The Views Data Export module, as its name indicates, is based on the Core Views
module to configure and set up CSV exports. Using Views we can then configure
an export for any Drupal 8 entities, usually for a particular bundle. We then
need to configure as many views as we need to export and some limitations may
appear when it comes to exporting multiple fields. Setting up CSV export with
this module requires administrative rights to create and manage views of a
Drupal 8 project, with some understanding of how Views works. It is therefore
not really intended for end users.

The Content Export CSV module allows you to quickly export the nodes of a
Drupal 8 site. Its configuration options are very limited, especially the
choice of exported fields and their values, in addition to the fact that only
nodes can be exported with this module. Conversely, this module can be used
directly by end users.

The Entity Content Export module allows many configuration options. It can
export all Drupal 8 content entities and the exports of each entity can be
configured based on the entity view modes, including field formatters that we
can select and configure for a given content export. However, it requires a
consequent initial configuration, with very high administrative access rights,
at the level of each entity bundle that we want to make exportable.

Entity Export CSV try to be so easily usable as Content Export CSV can be, and
so customizable and configurable as the Entity Content Export end/or
Views Data Export modules can be.

INSTALLATION
------------

Install as you would normally install a contributed Drupal module. See:
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
for further information.

CONFIGURATION
-------------

The configuration page is under the menu Configuration > Content authoring >
Entity Export CSV settings (/admin/config/content/entity-export-csv/settings).

This configuration page allows you to select which content entities will be
exportable from among the content entities present in a project and, if
necessary, to limit them to one or more bundles, and also to limit (or not)
the fields of these entity bundles that will be exportable.

USAGE
-----

The export page is under the menu Content > Export CSV
(/admin/content/entity-export-csv). This export page allows, on the basis of
the initial selection configuration, the entity to be exported, then to
configure for each field whether it should be included in the export and if
so how it should be exported. Field multiple are supported, and also field with
multiple properties, i.e. you can export each property of a field into the same
column or into separate columns belong your needs, and this for single or
multiple field (unlimited cardinality).

EXTENDING ENTITY EXPORT CSV
---------------------------
The Entity Export CSV module relies on a Plugin system to export all fields
and can therefore be easily extended by a Drupal 8 developer to support any
type of special case or fields created by contributed modules (for example,
the module includes a Plugin for the fields of the Geolocation module and
the Address module).

To create a Field Export Plugin, a FieldTypeExport Plugin must therefore be
created in the src/Plugin/FieldTypeExport namespace of any Drupal 8 module.

The annotations of this plugin allow you to control certain behaviors of the
Plugin and its availability. Let's look at these annotations with the example
of the Geolocation plugin included in the module.

@code
namespace Drupal\entity_export_csv\Plugin\FieldTypeExport;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_export_csv\Plugin\FieldTypeExportBase;
use Drupal\Core\Field\FieldItemInterface;

/**
 * Defines a Geolocation field type export plugin.
 *
 * @FieldTypeExport(
 *   id = "geolocation_export",
 *   label = @Translation("Geolocation export"),
 *   description = @Translation("Geolocation export"),
 *   weight = 0,
 *   field_type = {
 *     "geolocation",
 *   },
 *   entity_type = {},
 *   bundle = {},
 *   field_name = {},
 *   exclusive = FALSE,
 * )
 */
class GeolocationExport extends FieldTypeExportBase {
  // Stuff.
}
@endcode

The annotations of a FieldTypeExport plugin are :

  * weight: the weight of the plugin. The plugin with the highest weight will
    be the plugin selected by default if more than one plugin is available for
    a field.
  * field_type: the type of field to which the plugin applies. Multiple field
    types can be specified if necessary. This option is mandatory.
  * entity_type: it is possible here to limit the plugin to only certain entity
    types. Leave empty and the plugin will be available for the field type on
    any entity type.
  * bundle: it is possible here to limit the plugin to only certain entity
    bundles. Leave empty and the plugin will be available for the field type
    on any bundles
  * field_name: here it is possible to limit the plugin to one particular
    field. Leave empty and the plugin will be available for the field type on
    all fields of that type.
  * exclusive: this option if set to TRUE will make this plugin exclusive, i.e.
    all other plugins available for this field type will no longer be visible.
    Useful if you want to limit the options available to export a specific
    field by a particular field. Default value is FALSE.

You can then override all the methods available on the Base Plugin in order to
customize the export rendering of the field. In particular you can expose new
configuration options, and of course implement the massageExportPropertyValue()
method which is in charge of formatting the export of a field instance.

RESOURCES
---------

Blog posts about Entity Export CSV :
  * https://www.flocondetoile.fr/blog/export-content-csv-drupal-8

TROUBLESHOOTING
---------------


FAQ
---


MAINTAINERS
-----------

Current maintainers:
 * flocondetoile - https://drupal.org/u/flocondetoile
