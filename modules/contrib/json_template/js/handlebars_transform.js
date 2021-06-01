(function (Drupal, Handlebars) {
  Drupal.jsonTemplate.plugins.handlebars = function (template, data) {
    var compiled = Handlebars.compile(template);
    return compiled(data);
  }
})(Drupal, Handlebars);
