/**
* @file
*/

(function ($, Drupal) {

    'use strict';

    /**
     * @type {Drupal~behavior}
     */
    Drupal.behaviors.importantInformationFloatingContainer = {

        attach: function (context, settings) {

            // Keep the Floating Container at the right size
            var floatingContainerResize = function() {
                $('#block-importantinformationfloatingcontainer').css('position', $("body").height() + $("#block-importantinformationfloatingcontainer").innerHeight() > $(window).height() ? "inherit" : "fixed");
            };
            $(window).resize(floatingContainerResize).ready(floatingContainerResize);

            // Expand and contract Floating Container
            var expandable = drupalSettings.important_information.importantInformationFloatingContainer.expandable;
            var expandMarkup = drupalSettings.important_information.importantInformationFloatingContainer.expandMarkup;
            var shrinkMarkup = drupalSettings.important_information.importantInformationFloatingContainer.shrinkMarkup;
            var expandAmount = drupalSettings.important_information.importantInformationFloatingContainer.expandAmount;
            var heightAmount = drupalSettings.important_information.importantInformationFloatingContainer.heightAmount+'%';

            if (expandable) {

                $('.expand-button').click(function() {

                    if ($(this).hasClass('not-expanded')) {
                        $('#block-importantinformationfloatingcontainer').stop();
                        $('.expand-button').html(shrinkMarkup);
                        $('#block-importantinformationfloatingcontainer').animate({height:expandAmount+'%'}, 333, 'swing', function(){
                            $('.expand-button').addClass('expanded');
                            $('.expand-button').removeClass('not-expanded');
                        });
                    }

                    if ($(this).hasClass('expanded')) {
                        $('#block-importantinformationfloatingcontainer').stop();
                        $('.expand-button').html(expandMarkup);
                        $('#block-importantinformationfloatingcontainer').animate({height:heightAmount}, 333, 'swing', function(){
                            $('.expand-button').addClass('not-expanded');
                            $('.expand-button').removeClass('expanded');
                        });
                    }

                });

            }
        }
    };

    Drupal.behaviors.importantInformationHeightContainer = {
        attach: function (context, settings) {
            // Setting container height
            var heightAmount = drupalSettings.important_information.importantInformationHeightContainer.heightAmount+'%';
            $('#block-importantinformationfloatingcontainer').height(heightAmount);
            $('#block-importantinformationfloatingcontainer .content').height(heightAmount);

          // Hide / Show Floating Container
            var hide =  drupalSettings.important_information.importantInformationHeightContainer.hide;
            var offset =  drupalSettings.important_information.importantInformationHeightContainer.offset;

            if (hide == 1)  {

                $( window ).scroll(function() {

                    var position = window.scrollY + (jQuery(window).height() - jQuery('#block-importantinformationfloatingcontainer').height()) + (parseInt(offset));
                    // Position value refers to bottom of the viewport (the bottom edge of the user's view)
                    // We subtract the value of the height of the Floating Container  because it makes the viewport smaller
                    var bottomStart = jQuery('.block-embedded-bottom').offset().top;

                    //  Check  if the  Embedded  Bottom is viewable
                    if (position >= bottomStart ) {
                        jQuery('#block-importantinformationfloatingcontainer').hide();
                    }

                    if (position <=  bottomStart)  {
                        jQuery('#block-importantinformationfloatingcontainer').show();
                    }
                });
            }
        }
    };

})(jQuery, Drupal);
