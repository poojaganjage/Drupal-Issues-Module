<?php

namespace Drupal\entityreference_view_mode\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Entity\EntityStorageInterface;  
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'field_example_simple_text' formatter.
 *
 * @FieldFormatter(
 *   id = "entityreference_view_mode_field_formatter",
 *   module = "entityreference_view_mode",
 *   label = @Translation("Content View Formatter"),
 *   field_types = {
 *     "entityreference_view_mode_field_type"
 *   }
 * )
 */
class EntityReferenceViewModeFieldFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      if (empty($item->target_type) || empty($item->content) || empty($item->view_mode)) {
        continue;
      }

      try {
        $entity = $this->entityTypeManager
          ->getStorage($item->target_type)
          ->load($item->content);
      }
      catch (\Exception $e) {
        continue;
      }

      if (empty($entity)) {
        continue;
      }

      $viewMode = str_replace($item->target_type . '.', '', $item->view_mode);

      $elements[$delta] = $this->entityTypeManager
        ->getViewBuilder($item->target_type)
        ->view($entity, $viewMode, $langcode);
    }

    return $elements;
  }

}
