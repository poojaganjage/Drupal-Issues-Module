<?php
namespace Drupal\context_mobile_condition\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Mobile_Detect;

/**
 * Provides a 'Mobile Detect' condition.
 *
 * @Condition(
 *    id = "mobile_detect_condition",
 *    label = @Translation("Mobile detect condition"),
 * )
 */
class MobileDetectCondition extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a HttpStatusCode condition plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
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
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['mobile_detect_condition'] = $form_state->getValue('mobile_detect_condition');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function summary()
  {
    return t('Select type');
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate()
  {
    $configuration = $this->getConfiguration();
    $detector = new Mobile_Detect;
    $is_mobile = $detector->isMobile();
    $is_tablet = $detector->isTablet();
    $is_computer = !$is_mobile && !$is_tablet ? TRUE : FALSE;

    foreach ($configuration['mobile_detect_condition'] as $key => $value) {
      if ($key === $value) {
        if ($is_mobile)
          return TRUE;
        if ($is_tablet)
          return TRUE;
        if ($is_computer)
          return TRUE;
      }
    }

    return FALSE;
  }

}
