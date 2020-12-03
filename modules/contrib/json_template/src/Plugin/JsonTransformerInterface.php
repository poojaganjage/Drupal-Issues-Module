<?php

namespace Drupal\json_template\Plugin;

/**
 * Defines an interface for JSON transformer plugins.
 */
interface JsonTransformerInterface {

  /**
   * Get provider type (module or theme).
   *
   * @return string
   *   'module' or 'theme'.
   */
  public function getProviderType();

  /**
   * Get library name.
   *
   * @return string
   *   Library name.
   */
  public function getLibrary();

  /**
   * Get library provider.
   *
   * @return string[]
   *   Array ['type', 'name'].
   */
  public function getLibraryProvider();

  /**
   * Get path to transformer file.
   *
   * @return string
   *   Path to transformer js file suitable to include in the library.
   */
  public function getTransformer();

  /**
   * Get (already localized) title.
   *
   * @return string
   *   Title.
   */
  public function getTitle();

  /**
   * Get description.
   *
   * @return string
   *   Description.
   */
  public function getDescription();

}
