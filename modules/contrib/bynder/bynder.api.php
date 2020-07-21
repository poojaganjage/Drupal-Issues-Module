<?php

/**
 * @file
 * Hooks related to Bynder.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the search query passed to the Bynder API.
 *
 * This is primarily used by \Drupal\bynder\Plugin\EntityBrowser\Widget\BynderSearch::getForm()
 * before it passes $query into the getMediaList() method in the API to display
 * a list of available Bynder assets to use as media entities.
 *
 * @param array $query
 *   An associative array containing the query fields and values.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The form state of the entity browser form.
 * @param \Drupal\bynder\Plugin\EntityBrowser\Widget\BynderSearch $widget
 *   The entity browser widget plugin.
 *
 * @see hook_form_entity_browser_ENTITY_BROWSER_ID_form_alter()
 * @see \Drupal\bynder\BynderApi::getMediaList()
 */
function hook_bynder_search_query_alter(array &$query, \Drupal\Core\Form\FormStateInterface $form_state, \Drupal\bynder\Plugin\EntityBrowser\Widget\BynderSearch $widget) {
  if ($property_value = $form_state->getValue(['filters', 'my_property'])) {
    $query['property_my_property'] = $property_value;
  }
}

/**
 * @} End of "addtogroup hooks".
 */
