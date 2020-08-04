(function ($) {

  Drupal.behaviors.simple_live_css = {
    attach: function (context, settings) {
      $('.js--live-css', context).once('simple_live_css').each(function () {
        new LiveCssEditor();
      });
    }
  }

}(jQuery));
