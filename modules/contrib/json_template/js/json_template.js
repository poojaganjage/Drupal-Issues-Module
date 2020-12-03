(function (Drupal, settings) {
  Drupal.jsonTemplate = Drupal.jsonTemplate || {};
  Drupal.jsonTemplate.plugins = Drupal.jsonTemplate.plugins || {};
  Drupal.jsonTemplate.render = function (data, template_id) {
    const transformer = settings.jsonTemplate[template_id]['transformer'];
    const template = settings.jsonTemplate[template_id]['template'];
    if (Drupal.jsonTemplate.plugins[transformer] != null) {
      return Drupal.jsonTemplate.plugins[transformer](template, data);
    }
  }
})(Drupal, drupalSettings);
