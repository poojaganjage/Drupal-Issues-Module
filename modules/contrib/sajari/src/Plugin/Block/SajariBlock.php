<?php

namespace Drupal\sajari\Plugin\Block;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\json_template\Plugin\JsonTemplateManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sajari search block.
 *
 * @Block(
 *   id = "sajari_search",
 *   admin_label = @Translation("Sajari search"),
 *   category = @Translation("Search")
 * )
 */
class SajariBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Sitewide configuration for Sajari module.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $sajariConfiguration;

  /**
   * Json templates manager.
   *
   * @var \Drupal\json_template\Plugin\JsonTemplateManagerInterface
   */
  protected $jsonTemplateManager;

  /**
   * Array config values.
   *
   * @var string[]
   */
  protected static $arrayConfigs = ['tabs', 'facets', 'sorts', 'ranges'];

  /**
   * SajariBlock constructor.
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              ImmutableConfig $sajari_configuration,
                              JsonTemplateManagerInterface $json_template_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->sajariConfiguration = $sajari_configuration;
    $this->jsonTemplateManager = $json_template_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')->get('sajari.config'),
      $container->get('plugin.manager.json_template.template')
    );
  }

  /**
   * Get names of array config keys.
   */
  public static function getArrayConfigs() {
    return static::$arrayConfigs;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'pipeline' => 'website',
      'resultsPerPage' => 10,
      'fields' => [
        "url",
        "title",
        "image",
        "description",
      ],
      'pagerEnabled' => TRUE,
      'trackingEnabled' => TRUE,
      'maxSuggestions' => 5,
      'inputPlaceholder' => $this->t('Enter your search term'),
      'param' => 'search',
      'filter' => 'type="article"',
      'filterEnabled' => TRUE,
      'summaryEnabled' => TRUE,
      'tabsDefault' => 'Alpha',
      'tabsEnabled' => TRUE,
      'tabs' => [
        [
          'title' => "Alpha",
          'filter' => "domain='alpha.site-showcase.com'",
        ],
        [
          'title' => "Beta",
          'filter' => "domain='beta.site-showcase.com'",
        ],
      ],
      'facetsEnabled' => TRUE,
      'facets' => [
        [
          'name' => "type",
          'title' => "Type",
        ],
        [
          'name' => "topics",
          'title' => "Topic",
        ],
      ],
      'sortsEnabled' => TRUE,
      'sortsDefault' => 'published_date',
      'sorts' => [
        [
          'name' => 'published_date',
          'title' => $this->t('Newest'),
        ],
        [
          'name' => 'rating',
          'title' => $this->t('Best'),
        ],
      ],
      'rangesEnabled' => TRUE,
      'ranges' => [
        [
          'name' => 'rating',
          'title' => $this->t('Rating'),
          'min' => 0,
          'max' => 100,
          'step' => 1,
        ],
      ],
      'template' => 'sajari_results',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    parent::setConfiguration($configuration);
    // BlockBase::setConfiguration uses NestedArray::mergeDeep(), which means
    // that array from form will be added to array from default configuration
    // instead of substituting it.
    $array_configs = array_merge(static::$arrayConfigs, ['fields']);
    foreach ($array_configs as $key) {
      if (!empty($configuration[$key])) {
        $this->configuration[$key] = $configuration[$key];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();
    $form = parent::blockForm($form, $form_state);
    if (empty($this->sajariConfiguration->get('project')) || empty($this->sajariConfiguration->get('collection'))) {
      $link = Link::createFromRoute($this->t('Sajari settings'), 'sajari.config');
      $form['warning'] = [
        '#type' => 'markup',
        '#markup' => $this->t('You should define globally sajari project and collection at %link', ['%link' => $link->toString()]),
        '#prefix' => '<div class="messages messages--warning">',
        '#suffix' => '</div>',
        '#weight' => -1,
      ];
    }
    $form['config_settings'] = [
      '#type' => 'vertical_tabs',
      '#default_tab' => 'sajari-tabs',
      '#weight' => 40,
    ];
    $form['pipeline'] = [
      '#title' => $this->t('Pipeline'),
      '#description' => $this->t('The pipeline which alters the results.'),
      '#type' => 'select',
      '#options' => [
        'website' => $this->t('Website'),
        'raw' => $this->t('Raw'),
      ],
      '#default_value' => $config['pipeline'],
      '#required' => TRUE,
      '#weight' => 2,
    ];
    $form['results_per_page'] = [
      '#title' => $this->t('Page size'),
      '#description' => $this->t('The number of results to return in each result set.'),
      '#type' => 'number',
      '#min' => 1,
      '#default_value' => $config['resultsPerPage'],
      '#required' => TRUE,
      '#weight' => 5,
    ];
    $form['fields'] = [
      '#title' => $this->t('Fields'),
      '#description' => $this->t('The fields to return in the resultset. If no fields are defined, default ones will be used.'),
      '#type' => 'textfield',
      '#default_value' => implode(',', $config['fields']),
      '#required' => FALSE,
      '#weight' => 10,
    ];
    $form['pager_enabled'] = [
      '#title' => $this->t('Enable pager'),
      '#description' => $this->t('Enable the display of the pager.'),
      '#type' => 'checkbox',
      '#default_value' => $config['pagerEnabled'],
      '#weight' => 15,
    ];
    $form['tracking_enabled'] = [
      '#title' => $this->t('Enable tracker'),
      '#description' => $this->t('Enable tracking of the results which are clicked.'),
      '#type' => 'checkbox',
      '#default_value' => $config['trackingEnabled'],
      '#weight' => 20,
    ];
    $form['summary_enabled'] = [
      '#title' => $this->t('Enable summary'),
      '#type' => 'checkbox',
      '#default_value' => $config['summaryEnabled'],
      '#Weight' => 25,
    ];
    $form['filter'] = [
      '#title' => $this->t('Filter'),
      '#description' => $this->t('Basic filter to apply for all results.'),
      '#type' => 'textfield',
      '#default_value' => $config['filter'],
      '#required' => FALSE,
      '#weight' => 30,
    ];
    $definitions = $this->jsonTemplateManager->getDefinitionsForId('sajari_search');
    $options = [];
    foreach ($definitions as $key => $definition) {
      $options[$key] = $definition['title'];
    }
    $form['template'] = [
      '#type' => 'select',
      '#title' => $this->t('Template'),
      '#options' => $options,
      '#default_value' => $config['template'],
      '#required' => TRUE,
      '#weight' => 35,
    ];
    $form['tab_query'] = [
      '#type' => 'details',
      '#title' => $this->t('Query'),
      '#group' => 'settings][config_settings',
    ];
    $form['tab_query']['query_param'] = [
      '#title' => $this->t('Query param'),
      '#description' => $this->t('The query parameter to use for the query.'),
      '#type' => 'textfield',
      '#default_value' => $config['param'],
      '#required' => TRUE,
    ];
    $form['tab_query']['input_placeholder'] = [
      '#title' => $this->t('Help'),
      '#description' => $this->t('Help text displayed in the search box.'),
      '#type' => 'textfield',
      '#default_value' => $config['inputPlaceholder'],
      '#required' => TRUE,
    ];
    $form['tab_query']['max_suggestions'] = [
      '#title' => $this->t('Max suggestions'),
      '#description' => $this->t('The maximum number of suggestions based on the entered text. Enter 0 to disable this functionality.'),
      '#type' => 'number',
      '#min' => 0,
      '#default_value' => $config['maxSuggestions'],
    ];
    $form += $this->verticalTabsElement($form_state, 'tabs', $this->t('Tabs'), $this->t('Enable the use of tabs for filtering.'), TRUE);
    $form += $this->verticalTabsElement($form_state, 'facets', $this->t('Facets'), $this->t('Enable the use of facets for filtering.'));
    $form += $this->verticalTabsElement($form_state, 'sorts', $this->t('Sorts'), $this->t('Custom results ordering.'), TRUE);
    $form += $this->verticalTabsElement($form_state, 'ranges', $this->t('Ranges'), $this->t('Show only results falling in defined ranges for properties.'));
    return $form;
  }

  /**
   * Vertical tabs element.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param string $name
   *   Machine name.
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $title
   *   Tab title.
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $description
   *   Element description.
   * @param bool $default
   *   If default can be selected.
   *
   * @return array
   *   Form element.
   */
  protected function verticalTabsElement(FormStateInterface $form_state, $name, $title, $description, $default = FALSE) {
    $element = [];
    $element['tab_' . $name] = [
      '#type' => 'details',
      '#title' => $title,
      '#group' => 'settings][config_settings',
    ];
    $element['tab_' . $name] += $this->arrayConfigElement($form_state, $name, $title, $description, $default);
    return $element;
  }

  /**
   * Method that creates sortable multivalue fields.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param string $name
   *   Config key.
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $title
   *   Human readable title.
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $description
   *   Description.
   * @param bool $default
   *   If element needs to provide selector for default value.
   *
   * @return array
   *   Form element.
   */
  protected function arrayConfigElement(FormStateInterface $form_state, $name, $title, $description, $default = FALSE) {
    $element = [];
    $config = $this->getConfiguration();
    $element[$name . '_enabled'] = [
      '#title' => $title,
      '#description' => $description,
      '#type' => 'checkbox',
      '#default_value' => $config[$name . 'Enabled'],
    ];
    $storage = $form_state->getStorage();
    if (!isset($storage[$name])) {
      $storage[$name] = $config[$name];
      $form_state->setStorage($storage);
    }
    $theme = $default ? 'sajari_multiple_value_form' : 'field_multiple_value_form';
    if ($default) {
      $options = [];
    }
    $element[$name] = [
      '#theme' => $theme,
      '#field_name' => $name,
      '#cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      '#cardinality_multiple' => TRUE,
      '#max_delta' => count($storage[$name]),
      '#type' => 'details',
      '#title' => $title,
      '#open' => TRUE,
      '#prefix' => '<div id="sajari-' . $name . '">',
      '#suffix' => '</div>',
      '#states' => [
        'collapsed' => [
          ':input[name="settings[tab_' . $name . '][' . $name . '_enabled]"]' => ['unchecked' => TRUE],
        ],
      ],
    ];
    $callback = $name . 'Form';
    foreach ($storage[$name] as $i => $name_config) {
      $element[$name][] = $this->$callback($name_config, $i);
      if ($default) {
        $options[$i] = '';
        // We need to take care both for tabs, which has no name, just title,
        // and others that have name.
        if ($this->configuration[$name . 'Default'] === $name_config['name']
          || $this->configuration[$name . 'Default'] === $name_config['title']) {
          $default_value = $i;
        }
      }
    }
    if ($default) {
      $element[$name]['default'] = [
        '#type' => 'radios',
        '#options' => $options,
        '#default_value' => $default_value,
        '#required' => TRUE,
      ];
    }
    $element[$name]['add_more'] = [
      '#type' => 'submit',
      '#name' => $name . '_add_more',
      '#value' => t('Add another item'),
      '#attributes' => ['class' => [$name . '-add-more-submit']],
      '#limit_validation_errors' => [['settings', $name]],
      '#submit' => [[get_class($this), 'addMoreSubmit']],
      '#ajax' => [
        'callback' => [get_class($this), 'addMoreAjax'],
        'wrapper' => 'sajari-' . $name,
        'effect' => 'fade',
      ],
    ];
    return $element;
  }

  /**
   * Ajax callback for the "Add another item" button.
   *
   * This returns the new page content to replace the page content made obsolete
   * by the form submission.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public static function addMoreAjax(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $name = substr($button['#name'], 0, -9);
    return $form['settings']['tab_' . $name][$name];
  }

  /**
   * Submission handler for the "Add another item" button.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   FormState.
   */
  public static function addMoreSubmit(array $form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    $button = $form_state->getTriggeringElement();
    $key = substr($button['#name'], 0, -strlen('_add_more'));
    $definition = \Drupal::service('config.typed')->getDefinition('block.settings.sajari_search');
    $array_keys = array_keys($definition['mapping'][$key]['sequence']['mapping']);
    $add = [];
    foreach ($array_keys as $array_key) {
      $add[$array_key] = '';
    }
    $storage[$key][] = $add;
    $form_state->setStorage($storage);
    $form_state->setRebuild();
  }

  /**
   * Submission handler for the "Add another item" button.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   FormState.
   *
   * @TODO All this construct with user input manipulation seems pretty ugly.
   * Can we get a better solution?
   */
  public static function removeSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    preg_match('/^(\w*)_remove_(\d*)$/', $button['#name'], $matches);
    $element = $matches[1];
    $key = $matches[2];
    if (is_null($element) || is_null($key)) {
      return [];
    }
    $storage = $form_state->getStorage();
    unset($storage[$element][$key]);
    $storage[$element] = array_values($storage[$element]);
    $form_state->setStorage($storage);
    $input = $form_state->getUserInput();
    unset($input['settings']['tab_' . $element][$element][$key]);
    if (isset($input['settings']['tab_' . $element][$element]['default'])) {
      $default = $input['settings']['tab_' . $element][$element]['default'];
      unset($input['settings']['tab_' . $element][$element]['default']);
      $default = array_search($default, array_keys($input['settings']['tab_' . $element][$element]), FALSE);
    }
    $input['settings']['tab_' . $element][$element] = array_values($input['settings']['tab_' . $element][$element]);
    if (isset($default)) {
      $input['settings']['tab_' . $element][$element]['default'] = $default;
    }
    $form_state->setUserInput($input);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['pipeline'] = $form_state->getValue('pipeline');
    $this->configuration['resultsPerPage'] = $form_state->getValue('results_per_page');
    $this->configuration['fields'] = $this->splitFields($form_state->getValue('fields'));
    $this->configuration['param'] = $form_state->getValue(['tab_query', 'query_param']);
    $this->configuration['maxSuggestions'] = $form_state->getValue(['tab_query', 'max_suggestions']);
    $this->configuration['pagerEnabled'] = $form_state->getValue('pager_enabled');
    $this->configuration['inputPlaceholder'] = $form_state->getValue(['tab_query', 'input_placeholder']);
    $this->configuration['trackingEnabled'] = $form_state->getValue('tracking_enabled');
    $this->configuration['tabsAllLAbel'] = $form_state->getValue('tabs_all_label');
    $this->configuration['defaultTab'] = $form_state->getValue('tabs_default');
    $this->configuration['filter'] = $form_state->getValue('filter');
    $this->configuration['filterEnabled'] = $this->configuration['filter'] ? TRUE : FALSE;
    $this->configuration['summaryEnabled'] = (boolean) $form_state->getValue('summary_enabled');
    $this->configuration['template'] = $form_state->getValue('template');
    foreach (static::$arrayConfigs as $config) {
      if (!$form_state->getValue(['tab_' . $config, $config . '_enabled'])) {
        $this->configuration[$config] = [];
      }
      else {
        $this->configuration[$config . 'Enabled'] = TRUE;
        $values = $form_state->getValue(['tab_' . $config, $config]);
        if (isset($values['default'])) {
          // Here we have title for tabs and name for sorts.
          if ($config === 'tabs') {
            $this->configuration[$config . 'Default'] = $values[$values['default']]['title'];
          }
          else {
            $this->configuration[$config . 'Default'] = $values[$values['default']]['name'];
          }
          unset($values['default']);
        }
        usort($values, static function ($a, $b) {
          return SortArray::sortByKeyInt($a, $b, '_weight');
        });
        foreach ($values as &$value) {
          unset($value['_weight']);
        }
        unset($value);
        $this->configuration[$config] = $values;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    if ($form_state->getValue('tracking_enabled')) {
      $fields = $this->splitFields($form_state->getValue('fields'));
      if (!in_array('url', $fields, TRUE)) {
        $form_state->setError($form['fields'], $this->t('You should include url field if tracking is enabled.'));
      }
    }
  }

  /**
   * Helper method to process fields input.
   */
  private function splitFields($string) {
    return array_map('trim', explode(',', $string));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $config['project'] = $this->sajariConfiguration->get('project');
    $config['collection'] = $this->sajariConfiguration->get('collection');
    if (!empty($config['template'])) {
      /* @var \Drupal\json_template\Plugin\JsonTemplateDefault $plugin */
      $plugin = $this->jsonTemplateManager->createInstance($config['template']);
      $config['template'] = $plugin->getPluginId();
    }
    else {
      unset($config['template']);
    }
    $id = Html::getUniqueId('sajari');
    $render = [
      '#markup' => '<div id="' . $id . '"></div>',
      '#attached' => [
        'library' => ['sajari/sajari.search'],
        'drupalSettings' => [
          'sajari' => [
            $id => $config,
          ],
        ],
      ],
      '#cache' => [
        'tags' => ['config:sajari.config'],
      ],
    ];
    if (isset($plugin)) {
      $plugin->attach($render);
    }
    return $render;
  }

  /**
   * Generates tab form elements.
   *
   * @param array $tab_config
   *   Tab config.
   * @param int $i
   *   Numeric array key.
   *
   * @return array[]
   *   Tab form elements.
   */
  protected function tabsForm(array $tab_config, int $i) {
    return [
      'title' => [
        '#type' => 'textfield',
        '#title' => $this->t('Title'),
        '#description' => $this->t('The title of the tab, displayed to users.'),
        '#default_value' => $tab_config['title'],
        '#required' => TRUE,
      ],
      'filter' => [
        '#type' => 'textfield',
        '#title' => $this->t('Filter'),
        '#description' => $this->t('The filter to apply, eg. domain=\'example.com\''),
        '#default_value' => $tab_config['filter'],
        '#required' => FALSE,
      ],
      'remove' => $this->removeButton('tabs', $i),
      '_weight' => $this->weightElement(count($tab_config) + 1, $i),
    ];
  }

  /**
   * Generates tab form elements.
   *
   * @param array $facet_config
   *   Tab config.
   * @param int $i
   *   Numeric array key.
   *
   * @return array[]
   *   Tab form elements.
   */
  protected function facetsForm(array $facet_config, int $i) {
    return [
      'name' => [
        '#type' => 'textfield',
        '#title' => $this->t('Name'),
        '#description' => $this->t('The field for filtering.'),
        '#default_value' => $facet_config['name'],
        '#required' => TRUE,
      ],
      'title' => [
        '#type' => 'textfield',
        '#title' => $this->t('Title'),
        '#description' => $this->t('The title of the facet, displayed to users.'),
        '#default_value' => $facet_config['title'],
        '#required' => TRUE,
      ],
      'remove' => $this->removeButton('facets', $i),
      '_weight' => $this->weightElement(count($facet_config) + 1, $i),
    ];
  }

  /**
   * Generates sort form elements.
   *
   * @param array $sort_config
   *   Sort config.
   * @param int $i
   *   Numeric array key.
   *
   * @return array[]
   *   Sort form elements.
   */
  protected function sortsForm(array $sort_config, int $i) {
    return [
      'name' => [
        '#type' => 'textfield',
        '#title' => $this->t('Name'),
        '#description' => $this->t('Name of the field used for sorting.'),
        '#default_value' => $sort_config['name'],
        '#required' => TRUE,
      ],
      'title' => [
        '#type' => 'textfield',
        '#title' => $this->t('Title'),
        '#description' => $this->t('The title of the sort, displayed to users.'),
        '#default_value' => $sort_config['title'],
        '#required' => TRUE,
      ],
      'remove' => $this->removeButton('sorts', $i),
      '_weight' => $this->weightElement(count($sort_config) + 1, $i),
    ];
  }

  /**
   * Generates range form elements.
   *
   * @param array $range_config
   *   Range config.
   * @param int $i
   *   Numeric array key.
   *
   * @return array[]
   *   Range form elements.
   */
  protected function rangesForm(array $range_config, int $i) {
    return [
      'name' => [
        '#type' => 'textfield',
        '#title' => $this->t('Name'),
        '#description' => $this->t('The field to which range is applied.'),
        '#default_value' => $range_config['name'],
        '#required' => TRUE,
      ],
      'title' => [
        '#type' => 'textfield',
        '#title' => $this->t('Title'),
        '#description' => $this->t('The title of the range, displayed to users.'),
        '#default_value' => $range_config['title'],
        '#required' => TRUE,
      ],
      'min' => [
        '#type' => 'number',
        '#title' => $this->t('Minimal value'),
        '#default_value' => $range_config['min'],
        '#required' => TRUE,
      ],
      'max' => [
        '#type' => 'number',
        '#title' => $this->t('Maximal value'),
        '#default_value' => $range_config['max'],
        '#required' => TRUE,
      ],
      'step' => [
        '#type' => 'number',
        '#title' => $this->t('Step'),
        '#description' => $this->t('Step of the range'),
        '#step' => 1,
        '#default_value' => $range_config['step'],
      ],
      'remove' => $this->removeButton('ranges', $i),
      '_weight' => $this->weightElement(count($range_config) + 1, $i),
    ];
  }

  /**
   * Create remove button for a row in multivalue config fields.
   *
   * @param string $name
   *   Config array key.
   * @param int $i
   *   Position in array.
   *
   * @return array
   *   Element.
   */
  protected function removeButton($name, $i) {
    return [
      '#type' => 'submit',
      '#name' => $name . '_remove_' . $i,
      '#value' => t('Remove this item'),
      '#attributes' => ['class' => [$name . '-remove-submit']],
      '#limit_validation_errors' => [],
      '#submit' => [[get_class($this), 'removeSubmit']],
      '#ajax' => [
        'callback' => [get_class($this), 'addMoreAjax'],
        'wrapper' => 'sajari-' . $name,
        'effect' => 'fade',
      ],
    ];
  }

  /**
   * Creates weight element for ordering.
   *
   * @param int $max
   *   Maximal count.
   * @param int $i
   *   Current ordinal number.
   *
   * @return array
   *   Weight element.
   */
  protected function weightElement($max, $i) {
    return [
      '#type' => 'weight',
      '#title' => $this->t('Weight for row @number', ['@number' => $i + 1]),
      '#title_display' => 'invisible',
      // Note: this 'delta' is the FAPI #type 'weight' element's property.
      '#delta' => $max,
      '#default_value' => $i,
      '#weight' => 100,
    ];
  }

}
