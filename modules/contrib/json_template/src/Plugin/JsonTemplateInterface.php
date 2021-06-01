<?php

namespace Drupal\json_template\Plugin;

/**
 * Defines an interface for JSON templates.
 */
interface JsonTemplateInterface {

  /**
   * Get path to template.
   *
   * @return string
   *   Path to template.
   */
  public function getPath();

  /**
   * Get template title (already localized).
   *
   * @return string
   *   Template title
   */
  public function getTitle();

  /**
   * Get template description.
   *
   * @return string
   *   Template description
   */
  public function getDescription();

  /**
   * Get transformer id for the template.
   *
   * @return string
   *   Transformer plugin id.
   */
  public function getTransformer();

  /**
   * Get provider type (module or theme).
   *
   * @return string
   *   'module' or 'theme'.
   */
  public function getProviderType();

  /**
   * Get template body.
   *
   * @return string
   *   Template.
   */
  public function getTemplate();

  /**
   * Get the library necessary for template rendering.
   *
   * @return string
   *   Drupal library id.
   */
  public function getLibrary();

  /**
   * Attach template and library to render array.
   *
   * @param array $render_array
   *   Render array.
   */
  public function attach(array &$render_array);

}
