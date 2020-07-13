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
 * @license https://www.drupal.org ABC
 *
 * @version "GIT: <1001>"
 *
 * @link www.google.com
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
 * @license https://www.drupal.org ABC
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
     * @param array $configuration A configuration array containing
     * information about the plugin instance about the plugin instance.
     * @param string $plugin_id The plugin_id for the plugin instance. 
     * @param array $plugin_definition The plugin implementation definition.
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
     * @param $container The container variable.
     * @param $configuration The configuration variable.
     * @param $plugin_id The plugin id variable.
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
     * @param $form Build the form using $form varibale using.
     * @param $form_state Build the form using $form_state interface.
     *
     * @return string
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
        $configuration = $this->getConfiguration();
        $form['mobile_detect_condition'] = [
        '#title' => $this->t('Mobile detect'),
        // '#type' => 'radios',
        '#type' => 'checkbox',
        '#options' => array(
        '0' => $this->t('Mobile Device'),
        '1' => $this->t('Tablet Device'),
        '2' => $this->t('Computer device'),
        ),
        '#default_value' => isset($configuration['mobile_detect_condition']) 
        && !empty($configuration['mobile_detect_condition']) ? 
        $configuration['mobile_detect_condition'] : 0,
        ];

        return $form;
    }

    /**
     * Defines form and form state interface and submit configuration form.
     *
     * Submit the form using $form and $form_state variable using.
     *
     * @param $form Submit the form using $form varibale using.
     * @param $form_state Submit the form using $form_state interface.
     *
     * @return string
     */
    public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
        $this->configuration['mobile_detect_condition']
            = $form_state->getValue('mobile_detect_condition');
            parent::submitConfigurationForm($form, $form_state);
    }

    /**
     * Define summary.
     *
     * @return string
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
        $detect = new Mobile_Detect;
        $a = $detect->isMobile();
        $b = $detect->isTablet();
        $deviceType = $detect->isMobile() ? 'tablet' : 'computer';
        $scriptVersion = $detect->getScriptVersion();

        return TRUE;
    }

}
