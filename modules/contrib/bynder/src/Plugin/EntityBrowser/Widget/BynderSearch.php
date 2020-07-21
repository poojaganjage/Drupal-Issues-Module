<?php

namespace Drupal\bynder\Plugin\EntityBrowser\Widget;

use Drupal\bynder\Exception\BundleNotBynderException;
use Drupal\bynder\Exception\BundleNotExistException;
use Drupal\bynder\Plugin\media\Source\Bynder;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\media\MediaInterface;

/**
 * Uses a Bynder API to search and provide entity listing in a browser's widget.
 *
 * @EntityBrowserWidget(
 *   id = "bynder_search",
 *   label = @Translation("Bynder search"),
 *   description = @Translation("Adds an Bynder search field browser's widget.")
 * )
 */
class BynderSearch extends BynderWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'media_type_document' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['submit_text']['#access'] = FALSE;

    foreach ($this->entityTypeManager->getStorage('media_type')->loadMultiple() as $type) {
      /** @var \Drupal\media\MediaTypeInterface $type */
      if ($type->getSource() instanceof Bynder) {
        $form['media_type']['#options'][$type->id()] = $type->label();
      }
    }

    $form['media_type']['#title'] = $this->t('Media type (Image)');

    if (empty($form['media_type']['#options'])) {
      $form['media_type']['#disabled'] = TRUE;
      $form['media_type']['#description'] = $this->t('You must @create_type before using this widget.', [
        '@create_type' => Link::createFromRoute($this->t('create a Bynder media type'), 'entity.media_type.add_form')
          ->toString(),
      ]);
    }
    else {

      $form['media_type']['#required'] = FALSE;
      $form['media_type']['#empty_option'] = $this->t('- Hide images -');

      $form['media_type_document'] = [
        '#type' => 'select',
        '#title' => $this->t('Media type (Document)'),
        '#default_value' => $this->configuration['media_type_document'],
        '#required' => FALSE,
        '#options' => $form['media_type']['#options'],
        '#empty_option' => $this->t('- Hide documents -'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntities(array $form, FormStateInterface $form_state) {
    if (!$this->checkType()) {
      return [];
    }
    $media = [];
    $selection = Json::decode($form_state->getValue('bynder_selection', ''));
    $storage = $this->entityTypeManager->getStorage('media');

    if (!$selection) {
      return [];
    }

    $image_type = NULL;
    $image_source_field = NULL;
    $source_fields = [];
    if ($this->configuration['media_type']) {
      /** @var \Drupal\media\MediaTypeInterface $image_type */
      $image_type = $this->entityTypeManager->getStorage('media_type')
        ->load($this->configuration['media_type']);
      $image_source_field = $image_type->getSource()->getConfiguration()['source_field'];
      $source_fields[] = $image_source_field;
    }

    $document_type = NULL;
    $document_source_field = NULL;
    if ($this->configuration['media_type_document']) {
      /** @var \Drupal\media\MediaTypeInterface $document_type */
      $document_type = $this->entityTypeManager->getStorage('media_type')
        ->load($this->configuration['media_type_document']);
      $document_source_field = $document_type->getSource()->getConfiguration()['source_field'];
      if ($document_source_field != $image_source_field) {
        $source_fields[] = $document_source_field;
      }
    }

    foreach ($selection as $bynder_info) {
      $query = $storage->getQuery();

      $source_field_condition = $query->orConditionGroup();
      foreach ($source_fields as $source_field) {
        $source_field_condition->condition($source_field, $bynder_info['id']);
      }

      $mid = $query
        ->condition($source_field_condition)
        ->range(0, 1)
        ->execute();
      if ($mid) {
        $media[] = $storage->load(reset($mid));
      }
      else {
        if ($bynder_info['type'] == 'IMAGE' && $image_type) {
          $media[] = $storage->create([
            'bundle' => $image_type->id(),
            $image_source_field => $bynder_info['id'],
            'name' => $bynder_info['name'],
          ]);
        }
        elseif ($bynder_info['type'] == 'DOCUMENT' && $document_type) {
          $media[] = $storage->create([
            'bundle' => $document_type->id(),
            $document_source_field => $bynder_info['id'],
            'name' => $bynder_info['name'],
          ]);
        }
      }
    }
    return $media;
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);

    if ($form_state->getValue('errors')) {
      $form['actions']['submit']['#access'] = FALSE;
      return $form;
    }

    $form['bynder_selection'] = [
      '#type' => 'hidden',
      '#weight' => -1,
    ];

    $form['#attached']['library'][] = 'bynder/search_view';
    $form['#attached']['drupalSettings']['bynder']['domain'] = $this->config->get('bynder.settings')->get('account_domain');
    $form['#attached']['drupalSettings']['bynder']['types'] = [];

    if ($this->configuration['media_type']) {
      $form['#attached']['drupalSettings']['bynder']['types'][] = 'image';
    }
    if ($this->configuration['media_type_document']) {
      $form['#attached']['drupalSettings']['bynder']['types'][] = 'document';
    }

    $form['actions']['submit']['#attributes']['class'][] = 'js-hide';

    $form['browser']['#markup'] = Markup::create('<div style="position: fixed; top: 44px; left: 0; right: 0; bottom: 0;" id="bynder-compactview"><div style="display: flex; height: 100%;"></div></div>');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    if (!empty($form_state->getTriggeringElement()['#eb_widget_main_submit'])) {
      try {
        $media = $this->prepareEntities($form, $form_state);
        array_walk($media, function (MediaInterface $media_item) {
          $media_item->save();
        });
        $this->selectEntities($media, $form_state);
      }
      catch (\UnexpectedValueException $e) {
        $this->messenger()->addError($this->t('Bynder integration is not configured correctly. Please contact the site administrator.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkType() {
    if (parent::checkType()) {
      return TRUE;
    }
    if ($this->configuration['media_type_document']) {
      /** @var \Drupal\media\MediaTypeInterface $type */
      $type = $this->entityTypeManager->getStorage('media_type')
        ->load($this->configuration['media_type_document']);

      if (!$type) {
        (new BundleNotExistException(
          $this->configuration['media_type_document']
        ))->logException()->displayMessage();
        return FALSE;
      }
      elseif (!($type->getSource() instanceof Bynder)) {
        (new BundleNotBynderException($type->label()))->logException()
          ->displayMessage();
        return FALSE;
      }
      return TRUE;
    }
    return FALSE;
  }

}
