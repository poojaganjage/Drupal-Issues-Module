<?php

namespace Drupal\github_embed\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Utility\Token;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter to embed Github source code.
 *
 * @Filter(
 *   id = "github_embed",
 *   title = @Translation("Github embed"),
 *   description = @Translation("Replace [github_embed] token with defined source file."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   settings = {
 *     "base_url" = "https://raw.githubusercontent.com",
 *     "repository_user" = NULL,
 *     "repository_name" = NULL
 *   },
 *   weight = 100,
 * )
 */
class GithubEmbed extends FilterBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Token $token) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['base_url'] = [
      '#title' => $this->t('Github base URL'),
      '#type' => 'url',
      '#default_value' => $this->settings['base_url'],
      '#description' => $this->t('Github URL without trailing slash. Default: @base_url', [
        '@base_url' => $this->settings['base_url'],
      ]),
    ];
    $form['repository_user'] = [
      '#title' => $this->t('Repository username'),
      '#type' => 'textfield',
      '#default_value' => $this->settings['repository_user'],
    ];
    $form['repository_name'] = [
      '#title' => $this->t('Repository name'),
      '#type' => 'textfield',
      '#default_value' => $this->settings['repository_name'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $text = $this->token->replace($text, [
      'settings' => $this->settings,
      'filter_process' => 'github_embed',
    ]);
    return new FilterProcessResult($text);
  }

}
