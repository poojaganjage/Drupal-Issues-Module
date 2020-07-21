(function ($, Drupal) {
    Drupal.behaviors.bynder_select2 = {
        attach: function (context, drupalSettings) {
            if (typeof drupalSettings.bynder_select2 != 'undefined') {
                $.each(drupalSettings.bynder_select2, function (id, options) {
                    $s2ElementOptions = {
                        allowClear: true,
                        multiple: options.multiple,
                        placeholder: options.placeholder_text,
                        width: '150px',
                        dropdownAutoWidth : true,
                        minimumInputLength: 0
                    }
                    if(($remoteDataVars = options.loadRemoteData)) {
                        $s2ElementOptions['delay'] = 1000;
                        $s2ElementOptions['minimumInputLength'] = 3;
                        $s2ElementOptions['ajax'] = {
                                url: $remoteDataVars.url,
                                dataType: 'json',
                            };
                    }
                    $s2Input = $(options.selector).select2($s2ElementOptions);
                });
            }
        }
    };
}(jQuery, Drupal));
