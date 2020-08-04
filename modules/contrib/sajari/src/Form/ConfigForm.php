<?php

namespace Drupal\sajari\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Sitewide configuration for sajari search.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['sajari.config'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sajari_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('sajari.config');
    $form = parent::buildForm($form, $form_state);
    $form['project'] = [
      '#title' => $this->t('Project'),
      '#description' => $this->t('The project containing the collection.'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $config->get('project'),
    ];
    $form['collection'] = [
      '#title' => $this->t('Collection'),
      '#description' => $this->t('The collection to use for the query.'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $config->get('collection'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('sajari.config')
      ->set('project', $form_state->getValue('project'))
      ->set('collection', $form_state->getValue('collection'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
