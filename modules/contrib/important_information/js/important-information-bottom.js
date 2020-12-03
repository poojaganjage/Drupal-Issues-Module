/**
* @file
*/

(function ($, Drupal) {

    'use strict';

    /**
     * @type {Drupal~behavior}
     */
    Drupal.behaviors.importantInformationEmbeddedBottom = {
        
        attach: function (context, settings) {

            // Expand and contract Embedded Container
            var expandable = drupalSettings.important_information.importantInformationEmbeddedBottom.expandable;
            
            if (expandable) {

                let myLabels = document.querySelectorAll('.lbl-toggle');

                Array.from(myLabels).forEach(label => {
                  label.addEventListener('keydown', e => {
                    // 32 === spacebar
                    // 13 === enter
                    if (e.which === 32 || e.which === 13) {
                      e.preventDefault();
                      label.click();
                    };
                  });
                });

            }
        }
    };

})(jQuery, Drupal);
