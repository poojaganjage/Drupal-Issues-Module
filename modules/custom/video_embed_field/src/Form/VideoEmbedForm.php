<?php

namespace Drupal\video_embed_field\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

class VideoEmbedForm extends ConfigFormBase {
  
  public function getFormID() {
    return 'video_embed_field_admin_settings';
  }
  
  protected function getEditableConfigNames() {
    return [
      'video_embed_field.settings'
    ];
  }
  
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $types = node_type_get_names();
    $config = $this->config('video_embed_field.settings');
    $form['content_types_for_video_embed'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('The content types to enable Video Embed for'),
      '#default_value' => $config->get('allowed_types'),
      '#options' => $types,
    
    );
    $form['array_filter'] = array('#type' => 'value', '#value' => TRUE);
    return parent::buildForm($form,$form_state);
    }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $allowed_types = array_filter($form_state->getValue('content_types_for_video_embed'));
    sort($allowed_types);
    $this->config('video_embed_field.settings')
      ->set('allowed_types', $allowed_types)
      ->save();
      parent::submitForm($form, $form_state);
  }
}