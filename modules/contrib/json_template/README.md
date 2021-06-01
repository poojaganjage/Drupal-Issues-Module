# json_template
Drupal module to transform JSON with JavaScript based templates.

Module provides an API for handling templates used in client-side rendering. Sitebuilders should not install it if not required by other modules.

## Installation

Module comes with plugins for handlebars templates. It assumes that handlebars library is downloaded to libraries/handlebars folder, so that path to handlebars.js file is libraries/handlebars/dist/handlebars.js. It can be downloaded either manually or installed as composer npm-asset package.
Other than that, one should follow normal Drupal module installation process.

## Usage

Module implements 2 types of yaml plugins: json_template.templates and json_template.transformers.
The latter one represents a library that implements templating engine, eg, handlebars. To make use of such a library, one should implement a small piece of javascript and yaml plugin.
For the example of javascript, see handlebars_transform.js file. One should implement a function with the syntax of a given library that transforms JSON into HTML with template.
Yaml plugin should reference this javascript file and the library itself. See json_template.json_template.transformers.yml for an example.

Template plugins implement templates. An example:

    simple_handlebars:
      title: 'Simple handlebars'
      description: 'Description of simple handlebars template'
      file: handlebars/simple.html.hbs
      transformer: handlebars
      available_for:
        - sajari_search

'file' is a path to template. Transformer is the id of transformer plugin described above. 'available_for' is a list of ids, by which templates can be filtered, eg block ids.

To list available templates:

    \Drupal::service('plugin.manager.json_template.template')->getDefinitionsForId('sajari_search');

To get the template itself:

    $plugin = \Drupal::service('plugin.manager.json_template.template')
        ->createInstance($plugin_id);
    $template = $plugin->getTemplate();

To supply the template to fontend, attaching necessary libraries, you should call

    $plugin->attach($render_array)

Note that this provides all the necessary information to frontend, but you are still responsible for incorporating template id into your data structure, so that you know how and when you will call it.

And the final step is to call the template in Javascript on some JSON data:

    Drupal.jsonTemplate.render(data, template_id);

### list_template field

Field type provides storage for template ids, if you need to associate template with some entity.
