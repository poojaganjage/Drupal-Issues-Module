<?php

namespace Drupal\important_information\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides an Important Information block that floats above the site.
 *
 * @Block(
 *   id = "floating",
 *   admin_label = @Translation("Important Information: Floating Container"),
 *   category = @Translation("Important Information")
 * )
 */
class Floating extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Load entities.
    $entities_floating = \Drupal::entityTypeManager()->getStorage('ii_content')->loadByProperties(['type' => 'floating']);
    $entities_bottom = \Drupal::entityTypeManager()->getStorage('ii_content')->loadByProperties(['type' => 'bottom']);

    foreach ($entities_bottom as $entityBottom) {
      if ($entityBottom->isPublished()) {
        $variables = $this->showEntity($entityBottom, $entities_floating);
        return $variables;
      }
      else {
        $cookie = $entityBottom->getCacheContexts();
        if (array_shift($cookie) == 'cookies:pfizer_full_preview_mode') {
          $variables = $this->showEntity($entityBottom, $entities_floating);
          return $variables;
        }
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function showEntity($entityBottom, $entities_floating) {
    foreach ($entities_floating as $entityFloating) {
      $container_hide = $entityFloating->get('field_hide_floating')->getString();
      $offset = $entityFloating->get('field_offset_floating')->getString();
      $full_size = $entityFloating->get('field_full_size_floating')->getString();
      $collapsed_percent = $entityFloating->get('field_per_collapsed_floating')->getString();
      $expandable = $entityFloating->get('field_expanded_floating')->getString();
      $expandable_amount = $entityFloating->get('field_per_expanded_floating')->getString();
      $expand_button_markup_value = $entityFloating->get('field_expand_button_floating')->value;
      $expand_button_markup_format = $entityFloating->get('field_expand_button_floating')->format;
      $shrink_button_markup_value = $entityFloating->get('field_unexpand_button_floating')->value;
      $shrink_button_markup_format = $entityFloating->get('field_unexpand_button_floating')->format;
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
      '#theme' => 'floating',
      '#information' => $information,
      '#attached' => [
        'library' => [
          'important_information/importantInformationFloatingContainer',
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
      $link_url = Url::fromRoute('important_information.floating');
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

    $variables['#attached']['drupalSettings']['important_information']['importantInformationHeightContainer'] = [
      'heightAmount' => render($collapsed_percent),
      'hide' => $container_hide ? $container_hide : FALSE,
      'offset' => $offset,
    ];

    // Check for Expandability.
    if ($expandable == 1) {
      $expand_button_markup = [
        '#type' => 'processed_text',
        '#text' => $expand_button_markup_value,
        '#format' => $expand_button_markup_format,
      ];

      $variables['#expandable'] = TRUE;
      $variables['#default_expand_markup'] = $expand_button_markup;
      $variables['#attached']['library'][] = 'core/drupal.dialog.ajax';

      $shrink_button_markup = [
        '#type' => 'processed_text',
        '#text' => $shrink_button_markup_value,
        '#format' => $shrink_button_markup_format,
      ];

      $variables['#attached']['drupalSettings']['important_information']['importantInformationFloatingContainer'] = [
        'expandable' => TRUE,
        'expandMarkup' => render($expand_button_markup),
        'shrinkMarkup' => render($shrink_button_markup),
        'expandAmount' => $expandable_amount,
        'heightAmount' => render($collapsed_percent),
      ];
    }
    else {
      $variables['#expandable'] = FALSE;

      $variables['#attached']['drupalSettings']['important_information']['importantInformationFloatingContainer'] = [
        'expandable' => FALSE,
      ];
    }

    return $variables;
  }

}
