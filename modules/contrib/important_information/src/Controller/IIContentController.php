<?php

namespace Drupal\important_information\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Url;
use Drupal\important_information\Entity\IIContentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Entity\EntityInterface;

/**
 * Class IIContentController.
 *
 *  Returns responses for II Content routes.
 */
class IIContentController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Constructs a new IIContentController.
   *
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer.
   */
  public function __construct(DateFormatter $date_formatter, Renderer $renderer) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer')
    );
  }

  /**
   * Displays a II Content revision.
   *
   * @param int $ii_content_revision
   *   The II Content revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($ii_content_revision) {
    $ii_content = $this->entityTypeManager()->getStorage('ii_content')->loadRevision($ii_content_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('ii_content');

    return $view_builder->view($ii_content);
  }

  /**
   * Page title callback for a II Content revision.
   *
   * @param int $ii_content_revision
   *   The II Content revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($ii_content_revision) {
    $ii_content = $this->entityTypeManager()->getStorage('ii_content')->loadRevision($ii_content_revision);
    return $this->t(
      'Revision of %title from %date', [
        '%title' => $ii_content->label(),
        '%date' => $this->dateFormatter->format($ii_content->getRevisionCreationTime()),
      ]
    );
  }

  /**
   * Generates an overview table of older revisions of a II Content.
   *
   * @param \Drupal\important_information\Entity\IIContentInterface $ii_content
   *   A II Content object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(IIContentInterface $ii_content) {
    $account = $this->currentUser();
    $ii_content_storage = $this->entityTypeManager()->getStorage('ii_content');

    $langcode = $ii_content->language()->getId();
    $langname = $ii_content->language()->getName();
    $languages = $ii_content->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $ii_content->label()]) : $this->t('Revisions for %title', ['%title' => $ii_content->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all ii content revisions") || $account->hasPermission('administer ii content entities')));
    $delete_permission = (($account->hasPermission("delete all ii content revisions") || $account->hasPermission('administer ii content entities')));

    $rows = [];

    $vids = $ii_content_storage->revisionIds($ii_content);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {

      /**
       * @var \Drupal\important_information\IIContentInterface $revision
       */
      $revision = $ii_content_storage->loadRevision($vid);

      // Show revisions affected by the language that is being displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');

        if ($vid != $ii_content->getRevisionId()) {
          // $link = $this->l(
          // $date, new Url(
          //   'entity.ii_content.revision', [
          //     'ii_content' => $ii_content->id(),
          //     'ii_content_revision' => $vid,
          //   ]
          // )
          // );
          $link = Link::fromTextAndUrl($date, new Url(
             'entity.ii_content.revision', [
               'ii_content' => $ii_content->id(),
               'ii_content_revision' => $vid,
             ]
           )
          );
        }
        else {
          // $link = $ii_content->link($date);
          $link = $ii_content->toLink($date)->toString();
        }

        $row = [];

        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'message' => [
                '#markup' => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];

        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];

          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }

          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute(
                  'entity.ii_content.translation_revert', [
                    'ii_content' => $ii_content->id(),
                    'ii_content_revision' => $vid,
                    'langcode' => $langcode,
                  ]
              ) :
              Url::fromRoute(
                  'entity.ii_content.revision_revert', [
                    'ii_content' => $ii_content->id(),
                    'ii_content_revision' => $vid,
                  ]
              ),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute(
                  'entity.ii_content.revision_delete', [
                    'ii_content' => $ii_content->id(),
                    'ii_content_revision' => $vid,
                  ]
              ),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['ii_content_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

  /**
   * Helper function $name.
   */
  public function modal($name) {
    $value = "";
    $format = "";
    $title = "";
    $options = array();

    switch ($name) {
      case 'floating':
        $entitiesBottom = \Drupal::entityTypeManager()->getStorage('ii_content')->loadByProperties(['type' => 'bottom']);
        $entitiesFloating = \Drupal::entityTypeManager()->getStorage('ii_content')->loadByProperties(['type' => 'floating']);

        foreach ($entitiesBottom as $entityBottom) {
          $value = $entityBottom->get('field_ii_content_bottom')->value;
          $format = $entityBottom->get('field_ii_content_bottom')->format;
          $title = $entityBottom->get('field_content_title')->value;
        }
        foreach ($entitiesFloating as $entityFloating) {
          $modal_width = $entityFloating->get('field_per_modal')->getString() . '%';
          $options = [
            'dialogClass' => 'popup-dialog-class floating-dialog',
            'width' => $modal_width,
          ];
        }
        break;

      case 'sidebar':
        $entitiesBottom = \Drupal::entityTypeManager()->getStorage('ii_content')->loadByProperties(['type' => 'bottom']);
        $entitiesSidebar = \Drupal::entityTypeManager()->getStorage('ii_content')->loadByProperties(['type' => 'sidebar']);

        foreach ($entitiesBottom as $entityBottom) {
          $value = $entityBottom->get('field_ii_content_bottom')->value;
          $format = $entityBottom->get('field_ii_content_bottom')->format;
          $title = $entityBottom->get('field_content_title')->value;
        }

        foreach ($entitiesSidebar as $entitySidebar) {
          $modal_width = $entitySidebar->get('field_per_modal')->getString() . '%';
          $modal_open = $entitySidebar->get('field_modal_open')->getString();

          switch ($modal_open) {
            case '0':
              $class = 'popup-dialog-class sidebar-dialog';
              break;

            case '1':
              $class = 'popup-dialog-class sidebar-dialog-left';
              break;

            case '2':
              $class = 'popup-dialog-class sidebar-dialog-right';
              break;
          }

          $options = [
            'dialogClass' => $class,
            'width' => $modal_width,
          ];
        }
        break;

      case 'bottom':
        $entities = \Drupal::entityTypeManager()->getStorage('ii_content')->loadByProperties(['type' => 'bottom']);

        foreach ($entities as $entity) {
          $value = $entity->get('field_ii_content_bottom')->value;
          $format = $entity->get('field_ii_content_bottom')->format;
          $modal_width = $entity->get('field_per_modal')->getString() . '%';
          $title = $entity->get('field_content_title')->value;

          $options = [
            'dialogClass' => 'popup-dialog-class bottom-dialog',
            'width' => $modal_width,
          ];
        }
        break;

      case 'intro':
        $entitiesIntro = \Drupal::entityTypeManager()->getStorage('ii_content')->loadByProperties(['type' => 'acknowledge']);
        $entitiesBottom = \Drupal::entityTypeManager()->getStorage('ii_content')->loadByProperties(['type' => 'bottom']);

        foreach ($entitiesBottom as $entityBottom) {
          $title = $entityBottom->get('field_content_title')->value;
        }

        foreach ($entitiesIntro as $entityIntro) {
          $value = $entityIntro->get('field_ack_content')->value;
          $format = $entityIntro->get('field_ack_content')->format;
          $modal_width = $entityIntro->get('field_per_modal')->getString() . '%';

          if ($entityIntro->get('field_continue_button')->getString() == 1) {
            $button = t('Continue to site');
            $value = $entityIntro->get('field_ack_content')->value . '<a>' . $button . '</a>';
          }

          $options = [
            'dialogClass' => 'popup-dialog-class intro-dialog',
            'width' => $modal_width,
          ];
        }
        break;

      case 'exit':
        $entitiesBottom = \Drupal::entityTypeManager()->getStorage('ii_content')->loadByProperties(['type' => 'bottom']);
        $entitiesExit = \Drupal::entityTypeManager()->getStorage('ii_content')->loadByProperties(['type' => 'leaving']);

        foreach ($entitiesBottom as $entityBottom) {
          $title = $entityBottom->get('field_content_title')->value;
        }

        foreach ($entitiesExit as $entityExit) {
          $value = $entityExit->get('field_exit_content')->value . '<button id="ok-button">' . t('Continue') . '</button>';
          $format = 'full_html';
          $modal_width = $entityExit->get('field_per_modal')->getString() . '%';
          $options = [
            'dialogClass' => 'popup-dialog-class exit-dialog',
            'width' => $modal_width,
          ];
        }
        break;
    }

    $information = [
      '#type' => 'processed_text',
      '#text' => $value,
      '#format' => $format,
    ];

    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand(t($title), render($information), $options));

    return $response;
  }

}
