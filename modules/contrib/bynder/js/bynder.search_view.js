/**
 * @file
 */

(function ($, Drupal, drupalSettings) {

    'use strict';

    /**
     * Registers behaviours related to Bynder search view widget.
     */
    Drupal.behaviors.BynderSearchView = {
        attach: function () {

          $('#bynder-compactview').once('bynder-compactview').each(function() {
            BynderCompactView.open({
              mode: 'MultiSelect',
              defaultDomain: drupalSettings.bynder.domain,
              assetTypes: drupalSettings.bynder.types,
              container:  this,
              onSuccess: function(assets) {
                var selectedValues = [];

                // Simplify the data structure, only keep what we need.
                assets.forEach(function(asset) {
                  // Alert the user that the selection does not have a thumbnail.
                  if (asset.type === 'DOCUMENT' && !asset.originalUrl) {
                    alert(Drupal.t('The document "@name" is not public. Please mark the original as public.',{'@name': asset.name}));
                    return;
                  }
                  selectedValues.push({
                    'id': asset.databaseId,
                    'type': asset.type,
                    'name': asset.name
                  });
                });

                // If there are no assets, do not submit the form.
                if (selectedValues.length === 0) {
                  return;
                }

                // Append the selected assets ids to the bynder_selection hidden
                // input field, separated by a comma.
                var bynderSelection = $('input[name=bynder_selection]')[0];
                bynderSelection.value = JSON.stringify(selectedValues);

                // Trigger entity browser submit button click.
                $('.is-entity-browser-submit').click();
              }
            });
          })
        }
    };

}(jQuery, Drupal, drupalSettings));
