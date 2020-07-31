<?php

/**
 * @file
 * Contains rules.page.inc.
 *
 * Page callback for Rules entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Rules templates.
 *
 * Default template: rules.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_rules(array &$variables) {
  // Fetch Rules Entity Object.
  $rules = $variables['elements']['#rules'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
