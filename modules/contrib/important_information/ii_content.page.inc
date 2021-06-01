<?php

/**
 * @file
 * Contains ii_content.page.inc.
 *
 * Page callback for II Content entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for II Content templates.
 *
 * Default template: ii_content.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_ii_content(array &$variables) {
  // Fetch IIContent Entity Object.
  $ii_content = $variables['elements']['#ii_content'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
