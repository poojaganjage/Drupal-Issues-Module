/**
* @file
*/

(function ($, Drupal, drupalSettings) {

    'use strict';

    /**
     * @type {Drupal~behavior}
     */
    Drupal.behaviors.importantInformationExit = {

        attach: function (context, settings) {

            
            $.expr[":"].external = function(a) {		
                var linkhn = a.hostname.split('.').reverse();
                var linkHref = linkhn[1] + "." + linkhn[0];
                
                var domainhn = window.location.hostname.split('.').reverse();
                var domainHref = domainhn[1] + "." + domainhn[0];
            
                return !a.href.match(/^mailto\:/) && !a.href.match(/^tel\:/) && linkHref !== domainHref;
            };
            
            $("a:external").addClass("ext_link");
            
            $(function() {
    
                var url = null;
                
                $('a.ext_link').click(function(e){

                    window.url = $(this).attr('href');  

                    e.preventDefault();

                    // open a modal 
                    $('#block-importantinformationleavinginterstitialmodal .use-ajax').click();
                    $('#block-importantinformationsidebar').addClass("sidebartop");

                    // scroll to top
                    $("html, body").animate({ scrollTop: 0 }, "slow");

                });

                $('#ok-button').click(function() { 
                    window.location = window.url;
                });
                
            }); 
        }
    };

})(jQuery, Drupal);