<?php

namespace Drupal\bynder\Plugin\Field\FieldFormatter;

use Drupal\bynder\Plugin\media\Source\Bynder;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'Bynder Document' formatter.
 *
 * @FieldFormatter(
 *   id = "bynder_document",
 *   label = @Translation("Bynder (Document)"),
 *   field_types = {"string", "string_long"}
 * )
 */
class BynderDocumentFormatter extends BynderFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'link' => TRUE,
      'title_field' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Render as link'),
      '#description' => $this->t('Whether the URL should be shown as a link or just as a plain URL.'),
      '#default_value' => $this->getSetting('link'),
    ];

    $field_candidates = $this->getAttributesFieldsCandidates();
    $elements['title_field'] = [
      '#type' => 'select',
      '#options' => $field_candidates,
      '#title' => $this->t('Link Title field'),
      '#description' => $this->t('Select the name of the field that should be used for the link title. Falls back to the name of the file if not set.'),
      '#default_value' => $this->getSetting('title_field'),
      '#empty_option' => $this->t('- File name -'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $settings = $this->getSettings();
    $summary[] = $this->t('Link: @link', ['@link' => $settings['link'] ? $this->t('Yes') : $this->t('No')]);

    if ($settings['link']) {
      $field_candidates = $this->getAttributesFieldsCandidates();
      $summary[] = $this->t('Link title field: @field', ['@field' => $settings['title_field'] ? $field_candidates[$settings['title_field']] : $this->t('- File name -')]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $is_entityreference = $this->fieldDefinition->getType() == 'entity_reference';

    foreach ($items as $delta => $item) {

      /** @var \Drupal\media\MediaInterface $media */
      $media = $is_entityreference ? $item->entity : $items->getEntity();
      if (!$media) {
        continue;
      }
      $source_plugin = $media->getSource();
      if ($source_plugin instanceof Bynder && ($original = $source_plugin->getMetadata($media, 'original'))) {
        if ($this->getSetting('link')) {

          if ($this->getSetting('title_field') && $media->hasField($this->getSetting('title_field')) && !$media->get($this->getSetting('title_field'))->isEmpty()) {
            $title = $media->get($this->getSetting('title_field'))->value;
          }
          else {
            $title = basename($original);
          }

          $elements[$delta] = [
            '#type' => 'link',
            '#title' => $title,
            '#url' => Url::fromUri($original),
          ];
        } else {
          $elements[$delta] = [
            '#plain_text' => $original,
          ];
        }
      }
    }

    return $elements;
  }

}
