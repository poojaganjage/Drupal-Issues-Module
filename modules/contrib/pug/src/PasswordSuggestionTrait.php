<?php

namespace Drupal\pug;

/**
 * Provides a trait for entities that have an owner.
 */
trait PasswordSuggestionTrait {

  /**
   * Prepare the password suggestion items.
   *
   * @return array
   *   Return the list of configuration items.
   */
  public function suggestionsItems() {
    $config = \Drupal::config('pug.settings');

    return $this->suggestedItems = [
      'has_weaknesses' => [
        '#title' => t('Recommendation'),
        '#default_value' => $config->get('has_weaknesses') ?: t('Recommendations to make your password stronger:'),
      ],
      'password_length' => [
        '#title' => t('Minimum Password Length'),
        '#default_value' => (int) $config->get('password_length') ?: 8,
      ],
      'too_short' => [
        '#title' => t('For Short Length'),
        '#default_value' => $config->get('too_short') ?: t('Make it at least @length characters', ['@length' => $config->get('password_length') ?: 8]),
      ],
      'add_upperCase' => [
        '#title' => t('For Uppercase'),
        '#default_value' => $config->get('add_upperCase') ?: t('Add uppercase letters'),
      ],
      'add_lowerCase' => [
        '#title' => t('For Lowercase'),
        '#default_value' => $config->get('add_lowerCase') ?: t('Add lowercase letters'),
      ],
      'add_numbers' => [
        '#title' => t('For Numbers'),
        '#default_value' => $config->get('add_numbers') ?: t('Add numbers'),
      ],
      'add_punctuation' => [
        '#title' => t('For Punctuation'),
        '#default_value' => $config->get('add_punctuation') ?: t('Add punctuation'),
      ],
      'confirm_title' => [
        '#title' => t('Confirm Label'),
        '#default_value' => $config->get('confirm_title') ?: t('Passwords match:'),
      ],
      'confirm_success' => [
        '#title' => t('On Confirm Success'),
        '#default_value' => $config->get('confirm_success') ?: t('yes'),
      ],
      'confirm_failure' => [
        '#title' => t('On Confirm Failure'),
        '#default_value' => $config->get('confirm_failure') ?: t('no'),
      ],
      'strength_title' => [
        '#title' => t('Strength Title'),
        '#default_value' => $config->get('strength_title') ?: t('Password strength:'),
      ],
      'same_as_username' => [
        '#title' => t('If same as username'),
        '#default_value' => $config->get('same_as_username') ?: t('Make it different from your username'),
      ],
      'label_weak' => [
        '#title' => t('Label for weak'),
        '#default_value' => $config->get('label_weak') ?: t('Weak'),
      ],
      'label_fair' => [
        '#title' => t('Label for fair'),
        '#default_value' => $config->get('label_fair') ?: t('Fair'),
      ],
      'label_good' => [
        '#title' => t('Label for good'),
        '#default_value' => $config->get('label_good') ?: t('Good'),
      ],
      'label_strong' => [
        '#title' => t('Label for Strong'),
        '#default_value' => $config->get('label_strong') ?: t('Strong'),
      ],
    ];
  }

}
