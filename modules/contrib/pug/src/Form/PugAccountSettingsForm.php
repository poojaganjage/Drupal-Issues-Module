<?php

namespace Drupal\pug\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\AccountSettingsForm;
use Drupal\pug\PasswordSuggestionTrait;
use Drupal\Core\Cache\Cache;

/**
 * Configure user settings for this site.
 *
 * @internal
 */
class PugAccountSettingsForm extends AccountSettingsForm {

  use PasswordSuggestionTrait;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'system.site',
      'user.mail',
      'user.settings',
      'pug.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['registration_cancellation']['user_password_strength']['#weight'] = 20;
    $form['registration_cancellation']['pug_settings'] = [
      '#type' => 'details',
      '#title' => 'PUG Settings',
      '#open' => TRUE,
      '#description' => $this->t("Manage how should the password recommendations display on user add/edit form.  There are no required settings, but making any change will have the same effect on the form."),
      '#states' => [
        'invisible' => [
          'input[name="user_password_strength"]' => ['checked' => FALSE],
        ],
      ],
      '#weight' => 20,
    ];

    foreach ($this->suggestionsItems() as $key => $setting) {
      $form['registration_cancellation']['pug_settings'][$key] =
      [
        '#type' => "textfield",
      ] + $setting;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('pug.settings');
    foreach (array_keys($this->suggestionsItems()) as $element) {
      $config->set($element, $form_state->getValue($element));
    }
    $config->save();
    Cache::invalidateTags(['pug']);
  }

}
