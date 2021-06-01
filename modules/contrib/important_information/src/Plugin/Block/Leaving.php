<?php

namespace Drupal\important_information\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a 'Modal' Block that checks for exit of the II.
 *
 * @Block(
 *   id = "leaving",
 *   admin_label = @Translation("Important Information: Leaving Interstitial Modal"),
 * )
 */
class Leaving extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $entities = \Drupal::entityTypeManager()->getStorage('ii_content')->loadByProperties(['type' => 'leaving']);

    foreach ($entities as $entity) {
      if ($entity->isPublished()) {

        $link_url = Url::fromRoute('important_information.exit');
        $link_url->setOptions([
          'attributes' => [
            'class' => ['use-ajax', 'button', 'button--small', 'display-none'],
            'data-dialog-type' => 'modal',
          ],
        ]);

        return [
          '#type' => 'markup',
          '#markup' => Link::fromTextAndUrl(t('Exit modal'), $link_url)->toString(),
          '#attached' => [
            'library' => [
              'core/drupal.dialog.ajax',
              'important_information/importantInformationExit',
            ],

          ],
        ];
      }
    }
  }

}
