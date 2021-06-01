<?php

namespace Drupal\important_information\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides an Important Information block at the sidebar of the site.
 *
 * @Block(
 *   id = "sidebar",
 *   admin_label = @Translation("Important Information: Sidebar"),
 *   category = @Translation("Important Information")
 * )
 */
class Sidebar extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Load entities.
    $entities_sidebar = \Drupal::entityTypeManager()->getStorage('ii_content')->loadByProperties(['type' => 'sidebar']);
    $entities_bottom = \Drupal::entityTypeManager()->getStorage('ii_content')->loadByProperties(['type' => 'bottom']);

    foreach ($entities_bottom as $entityBottom) {
      if ($entityBottom->isPublished()) {
        $variables = $this->showEntity($entityBottom, $entities_sidebar);
        return $variables;
      }
      else {
        $cookie = $entityBottom->getCacheContexts();
        if (array_shift($cookie) == 'cookies:pfizer_full_preview_mode') {
          $variables = $this->showEntity($entityBottom, $entities_sidebar);
          return $variables;
        }
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function showEntity($entityBottom, $entities_sidebar) {
    foreach ($entities_sidebar as $entitySidebar) {
      $container_hide = $entitySidebar->get('field_hide_sidebar')->getString();
      $offset_bottom = $entitySidebar->get('field_offset_sidebar_bottom')->getString();
      $offset = $entitySidebar->get('field_offset_sidebar')->getString();
      $full_size = $entitySidebar->get('field_full_size_sidebar')->getString();
      $container_width = $entitySidebar->get('field_container_width_sidebar')->getString();
    }

    $important_information_value = $entityBottom->get('field_ii_content_bottom')->value;
    $important_information_format = $entityBottom->get('field_ii_content_bottom')->format;
    $content_title_value = $entityBottom->get('field_content_title')->value;
    $content_title_format = $entityBottom->get('field_content_title')->format;

    $information = [
      '#type' => 'processed_text',
      '#text' => $important_information_value,
      '#format' => $important_information_format,
    ];

    $variables = [
      '#type' => 'markup',
      '#theme' => 'sidebar',
      '#information' => $information,
      '#attached' => [
        'library' => [
          'important_information/importantInformationSidebar',
        ],
      ],
    ];

    $title = [
      '#type' => 'processed_text',
      '#text' => $content_title_value,
      '#format' => $content_title_format,
    ];

    $variables['#content_title'] = $title;

    // Check for Modal.
    if ($full_size == 1) {
      $link_url = Url::fromRoute('important_information.sidebar');
      $link_url->setOptions(
        [
          'attributes' => [
            'class' => ['use-ajax', 'button', 'button--small'],
            'data-dialog-type' => 'modal',
          ],
        ]
      );

      $variables['#modal'] = [
        '#type' => 'markup',
        '#markup' => Link::fromTextAndUrl(t('Full size'), $link_url)->toString(),
      ];

      $variables['#attached']['library'][] = 'core/drupal.dialog.ajax';
    }

    // Add block and module configuration to settings.
    $variables['#attached']['drupalSettings']['important_information']['importantInformationSidebar'] = [
      'sidebarYOffset' => $offset,
      'containerWidth' => $container_width,
      'hide' => $container_hide ? $container_hide : FALSE,
      'offsetBottom' => $offset_bottom,
    ];

    return $variables;
  }

}
