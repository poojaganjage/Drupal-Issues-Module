<?php

namespace Drupal\bynder\Plugin\Field\FieldFormatter;

use Drupal\bynder\Plugin\media\Source\Bynder;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use GuzzleHttp\Exception\ConnectException;

/**
 * Plugin implementation of the 'Bynder' formatter.
 *
 * @FieldFormatter(
 *   id = "bynder",
 *   label = @Translation("Bynder (Image)"),
 *   field_types = {"string", "string_long", "entity_reference"}
 * )
 */
class BynderFormatter extends BynderFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'thumbnail' => 'webimage',
      'alt_field' => '',
      'title_field' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    try {
      $derivatives = array_merge(
        ['mini', 'webimage', 'thul'],
        array_map(function ($item) {
          return $item['prefix'];
        }, $this->bynder->getDerivatives())
      );
    }
    catch (ConnectException $e) {
      $derivatives = [];
    }

    $elements['thumbnail'] = [
      '#type' => 'select',
      '#options' => array_combine($derivatives, $derivatives),
      '#title' => $this->t('Derivative'),
      '#description' => $this->t('Select the name of the derivative to be used to display the image. Besides custom derivatives that you created in Bynder there are also default thumbnail sizes available that can be used. Go to @form and reload derivatives.', ['@form' => Link::createFromRoute($this->t('Bynder configuration form'), 'bynder.configuration_form')->toString()]),
      '#default_value' => $this->getSetting('thumbnail'),
    ];

    $field_candidates = $this->getAttributesFieldsCandidates();
    $elements['alt_field'] = [
      '#type' => 'select',
      '#options' => $field_candidates,
      '#title' => $this->t('Alt attribute field'),
      '#description' => $this->t('Select the name of the field that should be used for the "alt" attribute of the image.'),
      '#default_value' => $this->getSetting('alt_field'),
      '#empty_value' => '',
    ];

    $elements['title_field'] = [
      '#type' => 'select',
      '#options' => $field_candidates,
      '#title' => $this->t('Title attribute field'),
      '#description' => $this->t('Select the name of the field that should be used for the "title" attribute of the image.'),
      '#default_value' => $this->getSetting('title_field'),
      '#empty_value' => '',
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $settings = $this->getSettings();
    $summary[] = $this->t('Derivative: @style', ['@style' => $settings['thumbnail']]);

    $field_candidates = $this->getAttributesFieldsCandidates();
    if (empty($settings['title_field'])) {
      $summary[] = $this->t('Title attribute not displayed (not recommended).');
    }
    else {
      $summary[] = $this->t('Title attribute field: @field', ['@field' => $field_candidates[$settings['title_field']]]);
    }

    if (empty($settings['alt_field'])) {
      $summary[] = $this->t('Alt attribute not displayed (not recommended).');
    }
    else {
      $summary[] = $this->t('Alt attribute field: @field', ['@field' => $field_candidates[$settings['alt_field']]]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $settings = $this->getSettings();
    $element = [];
    $is_entityreference = $this->fieldDefinition->getType() == 'entity_reference';

    foreach ($items as $delta => $item) {
      /** @var \Drupal\media\MediaInterface $media_entity */
      if ($media_entity = $is_entityreference ? $item->entity : $items->getEntity()) {
        /** @var \Drupal\media\MediaSourceInterface $source_plugin */
        $source_plugin = $media_entity->getSource();
        if ($source_plugin instanceof Bynder && ($thumbnails = $source_plugin->getMetadata($media_entity, 'thumbnail_urls'))) {
          $element['#attached']['library'][] = 'bynder/formatter';
          $element[$delta]['bynder_wrapper'] = [
            '#type' => 'container',
            '#attributes' => [
              'class' => ['bynder-wrapper'],
            ],
          ];
          $thumbnail_uri = isset($thumbnails[$settings['thumbnail']]) ? $thumbnails[$settings['thumbnail']] : $thumbnails['webimage'];
          $element[$delta]['bynder_wrapper']['image'] = [
            '#theme' => 'image',
            '#uri' => $thumbnail_uri,
            '#attributes' => ['class' => ['bynder-image']],
            // TODO width, height - we don't have this info (unless we
            // download the thumbnail). Would be nice to have support in the
            // API.
          ];
          if ($settings['title_field'] && $media_entity->hasField($settings['title_field']) && !$media_entity->get($settings['title_field'])->isEmpty()) {
            $element[$delta]['bynder_wrapper']['image']['#title'] = $media_entity->get($settings['title_field'])->value;
          }
          if ($settings['alt_field'] && $media_entity->hasField($settings['alt_field']) && !$media_entity->get($settings['alt_field'])->isEmpty()) {
            $element[$delta]['bynder_wrapper']['image']['#alt'] = $media_entity->get($settings['alt_field'])->value;
          }
          $this->renderer->addCacheableDependency($element[$delta]['bynder_wrapper']['image'], $item);
          $element[$delta]['bynder_wrapper']['usage_image'] = [
            '#theme' => 'image',
            '#uri' => \Drupal::moduleHandler()->getModule('bynder')->getPath() . '/images/icons/bynder-logo.png',
            '#alt' => 'usage-image',
            // @todo Information is not available yet. Fix when API supports it.
            '#title' => $this->t('Usage info is not available yet. Usage restriction level: @restriction', [
              '@restriction' => get_media_restriction($source_plugin->getMetadata($media_entity, 'propertyOptions')),
            ]),
            '#attributes' => ['class' => ['usage-image']],
            '#access' => AccessResult::allowedIfHasPermission($this->currentUser, 'view bynder media usage'),
          ];
          $this->renderer->addCacheableDependency($element[$delta]['bynder_wrapper']['usage_image'], $this->configFactory->get('bynder.settings'));
        }
      }
    }

    return $element;
  }

}
