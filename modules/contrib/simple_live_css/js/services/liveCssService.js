(function ($) {

  'use strict';

  /**
   * The live css service constructor.
   *
   * @constructor
   */
  let LiveCssService = function() {};

  LiveCssService.prototype.save = function(data) {

    let deferred = $.Deferred();

    $.ajax({
      url: '/live_css/save',
      type: 'POST',
      dataType: 'text',
      data: data,
      success: function (data) {
        deferred.resolve(data);
      },
      error: function (jqXHR, textStatus, errorThrown) {
        deferred.reject(textStatus);
      }
    });

    return deferred.promise();
  };

  LiveCssService.prototype.getCss = function() {

    let deferred = $.Deferred();

    $.ajax({
      url: '/live_css/get',
      type: 'GET',
      dataType: 'text',
      success: function (data) {
        deferred.resolve(data);
      },
      error: function (jqXHR, textStatus, errorThrown) {
        deferred.reject(textStatus);
      }
    });

    return deferred.promise();
  };

  window.LiveCssService = LiveCssService;

})(jQuery);
