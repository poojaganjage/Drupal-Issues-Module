/**
 * @file
 */

(function ($, Drupal) {

    'use strict';

    /**
     * @type {Drupal~behavior}
     */


    Drupal.behaviors.importantInformationSidebar = {
        attach: function (context, settings) {

            // Look for the point at which to turn the position from absolute to fixed
            var sidebarYOffset =  drupalSettings.important_information.importantInformationSidebar.sidebarYOffset;
            var containerWidth =  drupalSettings.important_information.importantInformationSidebar.containerWidth;
            var fixedStart = sidebarYOffset; //jQuery('#block-importantInformationSidebar').offset().top;

            jQuery('#block-importantinformationsidebar').css("width", containerWidth);

            window.onscroll = function(ev) {

                var position = window.scrollY;

                //  Check  if the  Embedded  Bottom is viewable
                if (position >= fixedStart) {
                    jQuery('#block-importantinformationsidebar').addClass("fixed-position");
                }

                if (position <=  fixedStart)  {
                    jQuery('#block-importantinformationsidebar').removeClass("fixed-position");
                } 

            };

            // Hide / Show Floating Container
            var hide =  drupalSettings.important_information.importantInformationSidebar.hide;
            var offset =  drupalSettings.important_information.importantInformationSidebar.offsetBottom;
            
            if (hide == 1)  {

                $( window ).scroll(function() {
                    
                    var position = window.scrollY + jQuery('#block-importantinformationsidebar').height() + (parseInt(offset));
                    // Position value refers to bottom of the viewport (the bottom edge of the user's view)
                    // We subtract the value of the height of the Floating Container  because it makes the viewport smaller
                    var bottomStart = jQuery('.block-embedded-bottom').offset().top;

                    //  Check  if the  Embedded  Bottom is viewable
                    if (position >= bottomStart ) {
                        jQuery('#block-importantinformationsidebar').hide();
                    }

                    if (position <=  bottomStart)  {
                        jQuery('#block-importantinformationsidebar').show();
                    }
                });
            }

        }
    };

})(jQuery, Drupal);
