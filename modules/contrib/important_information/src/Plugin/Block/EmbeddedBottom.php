<?php

namespace Drupal\important_information\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides an Important Information block that floats above the site.
 *
 * @Block(
 *   id = "embedded_bottom",
 *   admin_label = @Translation("Important Information: Embedded Bottom"),
 *   category = @Translation("Important Information")
 * )
 */
class EmbeddedBottom extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Load entity.
    $entities = \Drupal::entityTypeManager()->getStorage('ii_content')->loadByProperties(['type' => 'bottom']);

    foreach ($entities as $entity) {
      $cookie = $entity->getCacheContexts();
      if (array_shift($cookie) == 'cookies:pfizer_full_preview_mode') {
        $variables = $this->showEntity($entity);
        return $variables;
      }
      else {
        if ($entity->isPublished()) {
          $variables = $this->showEntity($entity);
          return $variables;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function showEntity($entity) {
    $important_information_value = $entity->get('field_ii_content_bottom')->value;
    $important_information_format = $entity->get('field_ii_content_bottom')->format;
    $full_size = $entity->get('field_full_size_bottom')->getString();
    $expandable = $entity->get('field_collapsible_bottom')->getString();
    $content_title_value = $entity->get('field_content_title')->value;
    $content_title_format = $entity->get('field_content_title')->format;

    $information = [
      '#type' => 'processed_text',
      '#text' => $important_information_value,
      '#format' => $important_information_format,
    ];

    $variables = [
      '#type' => 'markup',
      '#theme' => 'embedded_bottom',
      '#information' => $information,
      '#attached' => [
        'library' => [
          'important_information/importantInformationBottom',
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

      $link_url = Url::fromRoute('important_information.bottom');
      $link_url->setOptions([
        'attributes' => [
          'class' => ['use-ajax', 'button', 'button--small'],
          'data-dialog-type' => 'modal',
        ],
      ]);

      $variables['#modal'] = [
        '#type' => 'markup',
        '#markup' => Link::fromTextAndUrl(t('Full size'), $link_url)->toString(),
      ];

      $variables['#attached']['library'][] = 'core/drupal.dialog.ajax';
    }

    // Check for Expandability.
    if ($expandable == 1) {

      $expand_button_markup = [
        '#type' => 'processed_text',
        '#text' => $content_title_value,
        '#format' => $content_title_format,
      ];

      $variables['#expandable'] = TRUE;
      $variables['#default_expand_markup'] = $expand_button_markup;
      $variables['#attached']['library'][] = 'core/drupal.dialog.ajax';

      $variables['#attached']['drupalSettings']['important_information']['importantInformationEmbeddedBottom'] = [
        'expandable' => TRUE,
        'expandMarkup' => render($expand_button_markup),
      ];
    }
    else {
      $variables['#expandable'] = FALSE;
      $variables['#attached']['drupalSettings']['important_information']['importantInformationEmbeddedBottom'] = [
        'expandable' => FALSE,
      ];
    }

    return $variables;
  }

}
