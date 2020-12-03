/**
* @file
*/



function acknowledgeIntro() {
    jQuery.cookie("importantInformationAcknowledged", "true", { expires: 7 });
}

(function ($, Drupal) {

    'use strict';

    /**
     * @type {Drupal~behavior}
     */
    Drupal.behaviors.importantInformationAcknowledge = {


        attach: function (context, settings) {

            $('body', context).once('acknowledgeCheck').each(function () {
                // Check for cookie
                var value = jQuery.cookie('importantInformationAcknowledged');

                if (value !== "true")  {
                    jQuery('#block-importantinformationacknowledgementmodal .open-important-information-intro').click();
                }

            });

            $('body', context).once('acknowledgeUpdate').each(function () {
                $('.important-information-intro .ui-button.ui-icon-closethick').on('click', acknowledgeIntro());
            });

            $("#drupal-modal a").removeClass("ext_link");

            $('#drupal-modal a').click(function() {
                $(".ui-dialog .ui-dialog-titlebar-close").click();
            });

        }
    };

})(jQuery, Drupal);