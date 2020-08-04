<?php

namespace Drupal\simple_live_css\Controller;

use Drupal\Core\Asset\CssCollectionOptimizer;
use Drupal\Core\Asset\JsCollectionOptimizer;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\simple_live_css\Utility\InjectCssFileUtility;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class LiveCssController.
 *
 * @package Drupal\simple_live_css\Controller
 */
class LiveCssController extends ControllerBase {

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The CSS collection optimizer.
   *
   * @var \Drupal\Core\Asset\CssCollectionOptimizer
   */
  protected $cssCollectionOptimizer;

  /**
   * The JS collection optimizer.
   *
   * @var \Drupal\Core\Asset\JsCollectionOptimizer
   */
  protected $jsCollectionOptimizer;

  /**
   * Constructs a ConfigController object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system.
   * @param \Drupal\Core\Asset\CssCollectionOptimizer $css_collection_optimizer
   *   The CSS collection optimizer.
   * @param \Drupal\Core\Asset\JsCollectionOptimizer $js_collection_optimizer
   *   The JS collection optimizer.
   */
  public function __construct(FileSystemInterface $file_system, CssCollectionOptimizer $css_collection_optimizer, JsCollectionOptimizer $js_collection_optimizer) {
    $this->fileSystem = $file_system;
    $this->cssCollectionOptimizer = $css_collection_optimizer;
    $this->jsCollectionOptimizer = $js_collection_optimizer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_system'),
      $container->get('asset.css.collection_optimizer'),
      $container->get('asset.js.collection_optimizer')
    );
  }

  /**
   * Save the user entered live css.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   An HTTP response.
   */
  public function save(Request $request) {
    $content = $request->getContent();

    // If the file didn't exist yet, clear all the caches. This will make
    // sure the newly generated css file is loaded on the next request.
    if (!file_exists(InjectCssFileUtility::FILE_PATH)) {
      drupal_flush_all_caches();
    }

    $this->fileSystem->saveData($content, InjectCssFileUtility::FILE_PATH, FileSystemInterface::EXISTS_REPLACE);
    $this->fileSystem->chmod(InjectCssFileUtility::FILE_PATH);

    // Flush asset caches.
    $this->cssCollectionOptimizer->deleteAll();
    $this->jsCollectionOptimizer->deleteAll();
    Cache::invalidateTags(['library_info']);

    // Change the dummy query string appended to CSS and JavaScript
    // files. This forces all browsers to reload fresh files.
    _drupal_flush_css_js();

    return new Response('Success', 200);
  }

  /**
   * Get live css from file.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   An HTTP response.
   */
  public function get() {
    if (!file_exists(InjectCssFileUtility::FILE_PATH)) {
      return new Response('', 200);
    }

    $data = file_get_contents(InjectCssFileUtility::FILE_PATH);
    return new Response($data, 200);
  }

}
