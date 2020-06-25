<?php

namespace Drupal\taxonomy_import\Controller;
/**
 * Provides route responses for the import_csv module.
 */
class ImporttaxonomyController {
  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function importtaxonomy() {
    $element = array(
      '#markup' => create_taxonomy(),
    );
    return $element;
  } 
}




