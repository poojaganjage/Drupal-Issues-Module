<?php

namespace Drupal\bynder\Form;

use Drupal\bynder\BynderApiInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure bynder to enable OAuth based access.
 *
 * @package Drupal\bynder\Form
 */
class BynderConfigurationForm extends ConfigFormBase {

  /**
   * Bynder api service.
   *
   * @var \Drupal\bynder\BynderApiInterface
   *   Bynder api service.
   */
  protected $bynder;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Meta properties from Bynder.
   *
   * @var array
   */
  protected $metaProperties;

  /**
   * Derivatives information.
   *
   * @var array
   */
  protected $derivatives;

  /**
   * Constructs a BynderConfigurationForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\bynder\BynderApiInterface $bynder
   *   The Bynder API service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, BynderApiInterface $bynder, RendererInterface $renderer, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);
    $this->bynder = $bynder;
    $this->renderer = $renderer;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('bynder_api'),
      $container->get('renderer'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bynder_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['bynder.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('bynder.settings');

    $form['consumer_key'] = [
      '#required' => TRUE,
      '#type' => 'textfield',
      '#title' => $this->t('Consumer key'),
      '#parents' => ['credentials', 'consumer_key'],
      '#default_value' => $config->get('consumer_key'),
      '#description' => $this->t('Provide the consumer key. For more information check <a href="@url">Bynder knowlage base</a>.', [
        '@url' => 'https://support.getbynder.com/hc/en-us/articles/208734785-Create-API-tokens-for-your-app',
      ]),
    ];
    $form['consumer_secret'] = [
      '#required' => TRUE,
      '#type' => 'textfield',
      '#title' => $this->t('Consumer secret'),
      '#parents' => ['credentials', 'consumer_secret'],
      '#default_value' => $config->get('consumer_secret'),
      '#description' => $this->t('Provide the consumer secret. For more information check <a href="@url">Bynder knowlage base</a>.', [
        '@url' => 'https://support.getbynder.com/hc/en-us/articles/208734785-Create-API-tokens-for-your-app',
      ]),
    ];
    $form['token'] = [
      '#required' => TRUE,
      '#type' => 'textfield',
      '#title' => $this->t('Token'),
      '#parents' => ['credentials', 'token'],
      '#default_value' => $config->get('token'),
      '#description' => $this->t('Provide the token. For more information check <a href="@url">Bynder knowlage base</a>.', [
        '@url' => 'https://support.getbynder.com/hc/en-us/articles/208734785-Create-API-tokens-for-your-app',
      ]),
    ];
    $form['token_secret'] = [
      '#required' => TRUE,
      '#type' => 'textfield',
      '#title' => $this->t('Token secret'),
      '#default_value' => $config->get('token_secret'),
      '#parents' => ['credentials', 'token_secret'],
      '#description' => $this->t('Provide the token secret. For more information check <a href="@url">Bynder knowlage base</a>.', [
        '@url' => 'https://support.getbynder.com/hc/en-us/articles/208734785-Create-API-tokens-for-your-app',
      ]),
    ];
    $form['account_domain'] = [
      '#required' => TRUE,
      '#type' => 'textfield',
      '#title' => $this->t('Account domain'),
      '#default_value' => $config->get('account_domain'),
      '#parents' => ['credentials', 'account_domain'],
      '#description' => $this->t('Provide your Bynder account domain. It should be in the format "https://bynder-domain.extension". Change "bynder-domain.extension" with the domain provided by Bynder. For more information check <a href="@url">Bynder docs</a>.', [
        '@url' => 'http://docs.bynder.apiary.io/#reference/',
      ]),
    ];
    $form['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug'),
      '#description' => $this->t('Check this setting if you want to have more verbose log messages.'),
      '#default_value' => $config->get('debug'),
    ];
    $form['test'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('API connection test'),
      'wrapper' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => ['id' => 'connection-test'],
        '#attached' => ['library' => ['bynder/config_form']],
      ],
      'check' => [
        '#type' => 'button',
        '#limit_validation_errors' => [],
        '#value' => $this->t('Test connection'),
        '#ajax' => ['callback' => '::testConnectionAjaxCallback'],
      ],
      'test_connection' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Test connection before saving'),
        '#description' => $this->t("Uncheck to allow saving credentials even if connection to Bynder can't be established."),
        '#default_value' => TRUE,
      ],
    ];

    if ($derivatives = $this->getDerivatives()) {
      $form['derivatives'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Bynder image derivatives'),
        '#description' => $this->t('Bynder provides "mini", "webimage" and "thul" image sizes by default. Custom derivatives can be configured to better suit specific use-cases. Reload of derivatives can be triggered if any derivatives are missing from the list.'),
        'derivatives_list' => [
          '#theme' => 'item_list',
          '#items' => array_merge(
            ['mini', 'webimage', 'thul'],
            array_map(function ($item) { return $item['prefix']; }, $derivatives)
          ),
        ],
        'check' => [
          '#type' => 'submit',
          '#value' => $this->t('Update cached information'),
          '#submit' => [[static::class, 'submitReloadDerivatives']],
        ],
      ];
    }

    if ($meta_properties = $this->getMetaProperties()) {
      $options = [];
      foreach ($meta_properties as $key => $meta_property) {
        if ($meta_property['options']) {
          $options[$key] = bynder_get_applicable_label_translation($meta_property);
        }
      }

      $form['usage_wrapper'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Usage restriction metadata'),
      ];

      $form['usage_wrapper']['usage_metaproperty'] = [
        '#type' => 'select',
        '#options' => $options,
        '#default_value' => $config->get('usage_metaproperty'),
        '#description' => $this->t('Select metaproperty which is responsible for usage restriction. This is used to limit what assets can be used. If the information is not provided we assume royalty free.'),
        '#empty_value' => 'none',
        '#empty_option' => $this->t('- None -'),
        '#ajax' => [
          'callback' => '::usageAjaxCallback',
          'effect' => 'fade',
          'event' => 'change',
          'wrapper' => 'restrictions',
        ],
      ];

      $options = [];
      if ($form_state->getValue('usage_metaproperty') !== NULL) {
        foreach ($meta_properties[$form_state->getValue('usage_metaproperty')]['options'] as $meta_property_option) {
          $options[$meta_property_option['id']] = bynder_get_applicable_label_translation($meta_property_option);
        }
      }
      elseif ($config->get('usage_metaproperty') && $config->get('usage_metaproperty') !== 'none') {
        foreach ($meta_properties[$config->get('usage_metaproperty')]['options'] as $meta_property_option) {
          $options[$meta_property_option['id']] = bynder_get_applicable_label_translation($meta_property_option);
        }
      }

      $form['usage_wrapper']['restrictions'] = [
        '#type' => 'container',
        '#attributes' => ['id' => ['restrictions']],
        '#tree' => TRUE,
      ];

      if ($options) {
        $form['usage_wrapper']['restrictions']['royalty_free'] = [
          '#required' => TRUE,
          '#title' => 'Royalty free restriction level',
          '#type' => 'select',
          '#options' => $options,
          '#default_value' => $config->get('restrictions.royalty_free'),
          '#description' => $this->t('Select metaproperty option for assets that can be used everywhere.'),
        ];

        $form['usage_wrapper']['restrictions']['web_license'] = [
          '#required' => TRUE,
          '#title' => 'Web license restriction level',
          '#type' => 'select',
          '#options' => $options,
          '#default_value' => $config->get('restrictions.web_license'),
          '#description' => $this->t('Select metaproperty option for the assets that may be used only online.'),
        ];

        $form['usage_wrapper']['restrictions']['print_license'] = [
          '#required' => TRUE,
          '#title' => 'Print license restriction level',
          '#type' => 'select',
          '#options' => $options,
          '#default_value' => $config->get('restrictions.print_license'),
          '#description' => $this->t('Select metaproperty option for the assets that may be used only for print.'),
        ];
      }
    }
    else {
      $form['usage_wrapper'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Usage restriction metadata'),
      ];

      $form['usage_wrapper']['message'] = [
        '#markup' => $this->t('To set usage restriction metaproperty provide valid credentials first.'),
      ];
    }

    $form['metadata'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Local metadata'),
      '#description' => $this->t('Updates the Bynder media entities metadata information in a batch process.'),
    ];
    $form['metadata']['update_frequency'] = [
      '#type' => 'number',
      '#step' => 1,
      '#min' => 0,
      '#title' => $this->t('Metadata update frequency'),
      '#default_value' => $config->get('update_frequency'),
      '#field_suffix' => $this->t('seconds'),
      '#description' => $this->t('The update frequency to update local metadata information in seconds.'),
    ];
    $form['metadata']['batch_update'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update local metadata'),
      '#submit' => [[static::class, 'submitBatchMetadataUpdate']],
    ];

    $form['performance'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Performance settings'),
    ];

    $form['performance']['cache_lifetime'] = [
      '#type' => 'number',
      '#step' => 1,
      '#min' => 0,
      '#title' => $this->t('Cache life time'),
      '#default_value' => $config->get('cache_lifetime'),
      '#description' => $this->t('The maximum lifetime of cached media information in seconds.'),
    ];

    $form['performance']['timeout'] = [
      '#type' => 'number',
      '#step' => 1,
      '#min' => 1,
      '#title' => $this->t('Timeout'),
      '#default_value' => $config->get('timeout'),
      '#description' => $this->t('The timeout for all API requests to Bynder. Setting this too low might break uploading large files and other slow requests.'),
    ];

    $form['performance']['use_remote_images'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use remote image thumbnails of media and do not download them locally'),
      '#description' => $this->t('This can only be enabled if the <a href="@url">Remote Stream Wrapper module</a> is installed.', ['@url' => 'https://www.drupal.org/project/remote_stream_wrapper']),
      '#default_value' => $config->get('use_remote_images'),
      '#disabled' => !$this->moduleHandler->moduleExists('remote_stream_wrapper'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Ajax callback for usage metadata select field.
   */
  public function usageAjaxCallback(array &$form, FormStateInterface &$form_state) {
    $form_state->setRebuild(TRUE);
    return $form['usage_wrapper']['restrictions'];
  }

  /**
   * AJAX callback for test connection button.
   */
  public function testConnectionAjaxCallback(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $return_markup = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => ['id' => 'connection-test'],
    ];

    $credentials = $form_state->getValue('credentials');
    if ($this->testApiConnection($credentials['consumer_key'], $credentials['consumer_secret'], $credentials['token'], $credentials['token_secret'], $credentials['account_domain'])) {
      $return_markup['#value'] = $this->t('The API connection was established successfully.');
      $return_markup['#attributes']['style'] = 'color: green;';
    }
    else {
      $return_markup['#value'] = $this->t('Could not establish connection with Bynder. Check your credentials or <a href=":support">contact support.</a>', [':support' => 'mailto:support@getbynder.com']);
      $return_markup['#attributes']['style'] = 'color: red;';
    }

    $response->addCommand(new ReplaceCommand('#connection-test', $this->renderer->render($return_markup)));
    return $response;
  }

  /**
   * Submit callback that will update cached data.
   */
  public static function submitReloadDerivatives(array &$form, FormStateInterface $form_state) {
    \Drupal::service('bynder_api')->updateCachedData();
  }

  /**
   * Submit callback to trigger the metadata batch update.
   */
  public static function submitBatchMetadataUpdate(array &$form, FormStateInterface $form_state) {
    // Create a batch to process metadata of the Bynder media entities.
    $batch = [
      'operations' => [[BynderConfigurationForm::class . '::updateMetadataBatchWorker', []]],
      'finished' => BynderConfigurationForm::class . '::batchFinished',
      'title' => t('Updating local metadata...'),
      'error_message' => t('The update process has encountered an error.'),
    ];
    batch_set($batch);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $credentials = $form_state->getValue('credentials');
    foreach (['consumer_secret', 'token_secret'] as $name) {
      if (!ctype_alnum(trim($credentials[$name]))) {
        $form_state->setError($form[$name], $this->t('@label needs to contain only letters and numbers.', [
          '@label' => $form[$name]['#title']->render(),
        ]));
      }
    }

    foreach (['consumer_key', 'token'] as $name) {
      $parts = explode('-', trim($credentials[$name]));
      if (strlen($parts[0]) !== 8 || strlen($parts[1]) !== 4 || strlen($parts[2]) !== 4 || strlen($parts[3]) !== 16 || isset($parts[4])) {
        $form_state->setError($form[$name], $this->t('@label needs to use the pattern 8-4-4-16.', [
          '@label' => $form[$name]['#title']->render(),
        ]));
      }

      foreach ($parts as $part) {
        if (!ctype_alnum($part)) {
          $form_state->setError($form[$name], $this->t('@label needs to use only numbers and letters separated with "-".', [
            '@label' => $form[$name]['#title']->render(),
          ]));
          break;
        }
      }
    }

    // Makes sure we don't have a leading slash in the domain url.
    $credentials['account_domain'] = rtrim($credentials['account_domain'], '/');
    $form_state->setValue(['credentials','account_domain'], $credentials['account_domain']);

    if (!(substr(trim($credentials['account_domain']), 0, 8) === 'https://')
      || filter_var(trim($credentials['account_domain']), FILTER_VALIDATE_URL) === FALSE) {
      $form_state->setError($form['account_domain'], $this->t('Account domain expect a valid secure url format, as provided to you by Bynder: ":url".', [':url' => 'https://bynder-domain.extension/']));
    }

    if ($form_state->getValue('test_connection')) {
      if (!$form_state::hasAnyErrors() && !$this->testApiConnection($credentials['consumer_key'], $credentials['consumer_secret'], $credentials['token'], $credentials['token_secret'], $credentials['account_domain'])) {
        $form_state->setErrorByName('credentials', $this->t('Could not establish connection with Bynder. Check your credentials or <a href=":support">contact support.</a>', [':support' => 'mailto:support@getbynder.com']));
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $is_initial_save = $this->config('bynder.settings')->get('consumer_key') === '';
    $is_initial_save &= $this->config('bynder.settings')->get('consumer_secret') === '';
    $is_initial_save &= $this->config('bynder.settings')->get('token') === '';
    $is_initial_save &= $this->config('bynder.settings')->get('token_secret') === '';
    $account_domain = $this->config('bynder.settings')->get('account_domain');
    $is_initial_save &= $account_domain === '';

    $credentials = $form_state->getValue('credentials');
    $restrictions = $form_state->getValue('restrictions');
    $this->config('bynder.settings')
      ->set('consumer_key', $credentials['consumer_key'])
      ->set('consumer_secret', $credentials['consumer_secret'])
      ->set('token', $credentials['token'])
      ->set('token_secret', $credentials['token_secret'])
      ->set('account_domain', $credentials['account_domain'])
      ->set('debug', $form_state->getValue('debug'))
      ->set('cache_lifetime', $form_state->getValue('cache_lifetime'))
      ->set('update_frequency', $form_state->getValue('update_frequency'))
      ->set('timeout', $form_state->getValue('timeout'))
      ->set('usage_metaproperty', $form_state->getValue('usage_metaproperty'))
      ->set('restrictions.royalty_free', $restrictions['royalty_free'])
      ->set('restrictions.web_license', $restrictions['web_license'])
      ->set('restrictions.print_license', $restrictions['print_license'])
      ->set('use_remote_images', $form_state->getValue('use_remote_images'))
      ->save();

    parent::submitForm($form, $form_state);

    // If this is the first time we're configuring credentials also update
    // cached data.
    if ($is_initial_save || $account_domain !== $form_state->getValue('account_domain')) {
      try {
        $this->bynder->updateCachedData();
      }
      catch (\Exception $exception) {}
    }
  }

  /**
   * Batch operation worker to update the metadata of Bynder media entities.
   *
   * @param array $context
   *   The context array.
   */
  public static function updateMetadataBatchWorker(array &$context) {
    /** @var \Drupal\bynder\BynderService $bynder */
    $bynder = \Drupal::service('bynder');

    if (empty($context['sandbox']['total'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['current_id'] = -1;
      $context['sandbox']['total'] = $bynder->getTotalCountOfMediaEntities();
      $context['results']['updated'] = 0;
      $context['results']['skipped'] = 0;
    }

    $minimum_id = $context['sandbox']['current_id'] > 0 ? $context['sandbox']['current_id'] : NULL;

    $results = $bynder->updateMetadataLastMediaEntities($minimum_id);

    if (empty($results)) {
      $context['finished'] = 1;
      $context['sandbox']['progress'] = $context['sandbox']['total'];
      $context['results'] = [];
      return;
    }

    $context['sandbox']['current_id'] = $results['max_id'];
    $context['sandbox']['progress'] = $context['sandbox']['progress'] + $results['total'];
    $context['results']['updated'] = $context['results']['updated'] + count($results['updated']);
    $context['results']['skipped'] = $context['results']['skipped'] + count($results['skipped']);

    if ($context['sandbox']['progress'] < $context['sandbox']['total']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['total'];
    }
    else {
      $context['finished'] = 1;
    }

    $context['message'] = t('Updating local metadata @current of @total', [
      '@current' => $context['sandbox']['progress'],
      '@total' => $context['sandbox']['total'],
    ]);
  }

  /**
   * Finish callback for the batch processing.
   *
   * @param bool $success
   *   Whether the batch completed successfully.
   * @param array $results
   *   The results array.
   * @param array $operations
   *   The operations array.
   */
  public static function batchFinished($success, array $results, array $operations) {
    $messenger = \Drupal::messenger();
    if (!empty($results)) {
      $messenger->addStatus(t('Updated local metadata for @count media entities.', ['@count' => $results['updated']]));
      if ($results['skipped']) {
        $messenger->addWarning(t('Failed to update the local metadata for @count media entities missing in the remote system.', ['@count' => $results['skipped']]));
      }
    }
    else {
      $messenger->addWarning(t('There is an error with the connection to the Bynder service or there are no Bynder media entities to update.'));
    }
  }

  /**
   * Tests connection with the Bynder API.
   *
   * @param string $consumer_key
   *   Consumer key.
   * @param string $consumer_secret
   *   Consumer secret.
   * @param string $token
   *   Token.
   * @param string $token_secret
   *   Token secret.
   * @param string $account_domain
   *   Account domain.
   *
   * @return bool
   *   Whether communication was successfully established.
   */
  protected function testApiConnection($consumer_key, $consumer_secret, $token, $token_secret, $account_domain) {
    try {
      $this->bynder->setBynderConfiguration([
        'consumerKey' => $consumer_key,
        'consumerSecret' => $consumer_secret,
        'token' => $token,
        'tokenSecret' => $token_secret,
        'baseUrl' => $account_domain,
      ]);
      $this->bynder->getBrands();
    } catch (\Exception $exception) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Gets the meta properties from Bynder.
   *
   * @return array
   *   Returns meta properties from Bynder.
   */
  protected function getMetaProperties() {
    if (!$this->metaProperties) {
      try {
        $this->metaProperties = $this->bynder->getMetaproperties();
      } catch (\Exception $e) {
        return [];
      }
    }

    return $this->metaProperties;
  }

  /**
   * Gets the derivatives from Bynder.
   *
   * @return array
   *   Derivatives info.
   */
  protected function getDerivatives() {
    if (!$this->derivatives) {
      try {
        $this->derivatives = $this->bynder->getDerivatives();
      } catch (\Exception $e) {
        return [];
      }
    }

    return $this->derivatives;
  }

}
