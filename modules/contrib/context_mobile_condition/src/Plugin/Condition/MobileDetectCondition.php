<?php

/**
 * Provides context_mobile_condition Implementation.
 *
 * @category Module
 *
 * @package Contrib
 *
 * @author Display Name <username@example.com>
 *
 * @license https://www.drupal.org Drupal 8
 *
 * @version "GIT: <1001>"
 *
 * @link https://www.drupal.org
 */

namespace Drupal\context_mobile_condition\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Mobile_Detect;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Returns responses for context_mobile_condition module routes.
 *
 * @category Module
 *
 * @package Contrib
 *
 * @author Display Name <username@example.com>
 *
 * @license https://www.drupal.org Drupal 8
 *
 * @version "Release: 8"
 *
 * @link https://www.drupal.org
 *
 * Provides a 'Mobile Detect' condition.
 *
 * @Condition(
 *    id = "mobile_detect_condition",
 *    label = @Translation("Mobile detect condition"),
 * )
 */
class MobileDetectCondition extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The string translation information.
   *
   * @var Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * Constructs a HttpStatusCode condition plugin.
   *
   * @param $configuration      The configuration array for the plugin instance.
   * @param $plugin_id          The plugin_id for the plugin instance. 
   * @param $plugin_definition  The plugin implementation definition.
   * @param $string_translation The string translation information.   
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition,
    TranslationInterface $string_translation) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->stringTranslation = $string_translation;
  }

  /**
   * Create Method.
   *
   * @param $container         The container variable.
   * @param $configuration     The configuration variable.
   * @param $plugin_id         The plugin id variable.
   * @param $plugin_definition The plugin definition variable.
   *
   * @return object
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('string_translation')
    );
  }

  /**
   * Defines form and form state interface and build configuration form.
   *
   * Build the form using $form and $form_state variable using.
   *
   * @param $form       Build the form using $form varibale using.
   * @param $form_state Build the form using $form_state interface.
   *
   * @return string
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();
    $form['mobile_detect_condition'] = [
    '#title' => $this->t('Mobile detect'),
    '#type' => 'checkboxes',
    '#default_value' => isset($configuration['mobile_detect_condition']) ? $configuration['mobile_detect_condition'] : [],
    '#options' => array(
      'is_mobile' => $this->t('Mobile Device'),
      'is_tablet' => $this->t('Tablet Device'),
      'is_computer' => $this->t('Computer Device'),
    )
    ];

    return $form;
  }

  /**
   * Defines form and form state interface and submit configuration form.
   *
   * Submit the form using $form and $form_state variable using.
   *
   * @param $form       Submit the form using $form varibale using.
   * @param $form_state Submit the form using $form_state interface.
   *
   * @return string
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['mobile_detect_condition'] = $form_state->getValue('mobile_detect_condition');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * Define summary.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function summary() {
    return $this->t('Select type');
  }

  /**
   * Define evaluate.
   *
   * @return bool
   */
  public function evaluate() {
    $configuration = $this->getConfiguration();
    $detector = new Mobile_Detect;
    $is_mobile = $detector->isMobile();
    $is_tablet = $detector->isTablet();
    $is_computer = !$is_mobile && !$is_tablet ? TRUE : FALSE;

    foreach ($configuration['mobile_detect_condition'] as $key => $value) {
      if ($key === $value) {
          if ($is_mobile) {
              return TRUE;
          }
          if ($is_tablet) {
              return TRUE;
          }
          if ($is_computer) {
              return TRUE;
          }
      }
    }

    return FALSE;
  }

}
