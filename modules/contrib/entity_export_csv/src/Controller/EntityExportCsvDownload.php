<?php

namespace Drupal\entity_export_csv\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Define the entity export csv download controller.
 */
class EntityExportCsvDownload implements ContainerInjectionInterface {

  use StringTranslationTrait;
  
  /**
   * @var null|\Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The entity export csv download constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   */
  public function __construct(RequestStack $request_stack) {
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static ($container->get('request_stack'));
  }

  /**
   * Build the results page.
   *
   * @return array
   *   A Drupal render array.
   */
  public function resultsExport() {
    $request = $this->getRequest();

    if (!$request->query->has('results')) {
      throw new NotFoundHttpException(
        $this->t('Missing export download results.')
      );
    }
    $results = $request->query->get('results');

    $query_options = [
      'query' => [
        'file' => isset($results['file']) ? $results['file'] : NULL,
      ]
    ];
    $download_url = Url::fromRoute(
      'entity_content_export.download', [], $query_options
    )->toString();

    $build['results'] = [
      '#markup' => $this->t(
        "The download should automatically start shortly. If it doesn't, click 
         <a data-auto-download href='@download_url'>Download</a>.", [
          '@download_url' => $download_url
        ]
      ),
      '#attached' => [
        'library' => [
          'entity_content_csv/auto-download'
        ]
      ]
    ];

    $build['actions'] = [
      '#type' => 'actions',
    ];
    $build['actions']['back'] = [
      '#type' => 'link',
      '#title' => $this->t('Back to Export page.'),
      '#url' => Url::fromRoute('entity_export_csv.export_form'),
      '#options' => [
        'attributes' => [
          'class' => [
            'btn',
            'btn-primary',
            'btn-export-back',
          ],
        ],
      ],
    ];

    return $build;
  }

  /**
   * Download entity content exported file.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function downloadExport() {
    $file_uri = $this->getRequest()->query->get('file');

    if (!isset($file_uri) || !file_exists($file_uri)) {
      throw new NotFoundHttpException(
        $this->t('Missing or not found entity content exported file.')
      );
    }

    return (new BinaryFileResponse($file_uri))
      ->deleteFileAfterSend(TRUE)
      ->setContentDisposition('attachment', basename($file_uri))
   ;
  }

  /**
   * Get current request object.
   *
   * @return null|\Symfony\Component\HttpFoundation\Request
   */
  protected function getRequest() {
    return $this->request;
  }
}
