(function ($, Drupal, SajariDrupal, settings) {
  Drupal.behaviors.sajari = {
    attach: function attach(context, settings) {
      var configs = settings.sajari;
      for (var id in configs) {
        if (configs[id]['initialized']) {
          continue;
        }
        if (configs[id]['template'] != null) {
          configs[id]['resultsEnabled'] = true;
          configs[id]['resultsCallback'] = function (data) {
            return Drupal.jsonTemplate.render(data, configs[id]['template']);
          }
        }
        SajariDrupal.init(configs[id], id);
        configs[id]['initialized'] = true;
      }
    }
  }
}(jQuery, Drupal, SajariConfigurator, drupalSettings))
