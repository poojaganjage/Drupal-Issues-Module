(function ($, Drupal) {

  Drupal.behaviors.entity_export_csv_download = {
    attach: function (context, settings) {
      $('a[data-auto-download]', context).each(function(){
        let $this = $(this);
        setTimeout(function() {
          window.location = $this.attr('href');
        }, 2000);
      });
    }
  };

})(jQuery, Drupal);
