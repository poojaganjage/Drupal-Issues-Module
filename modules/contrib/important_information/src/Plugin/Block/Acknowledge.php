<?php

namespace Drupal\important_information\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a 'Modal' Block that checks for acknowledgement of the II.
 *
 * @Block(
 *   id = "acknowledge",
 *   admin_label = @Translation("Important Information: Acknowledgement Modal"),
 * )
 */
class Acknowledge extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $entities = \Drupal::entityTypeManager()->getStorage('ii_content')->loadByProperties(['type' => 'acknowledge']);

    foreach ($entities as $entity) {
      if ($entity->isPublished()) {
        $link_url = Url::fromRoute('important_information.intro');
        $link_url->setOptions([
          'attributes' => [
            'class' => [
              'use-ajax', 'button', 'button--small', 'open-important-information-intro',
            ],
            'data-dialog-type' => 'modal',
          ],
        ]);

        return [
          '#type' => 'markup',
          '#markup' => Link::fromTextAndUrl(t('Open modal'), $link_url)->toString(),
          '#attached' => [
            'library' => [
              'core/drupal.dialog.ajax',
              'important_information/importantInformationAcknowledge',
            ],
          ],
        ];
      }
    }
  }

}
