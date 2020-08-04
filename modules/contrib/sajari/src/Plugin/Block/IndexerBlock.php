<?php

namespace Drupal\sajari\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Block that performs page indexing for sajari.
 *
 * @Block(
 *   id = "sajari_indexer",
 *   admin_label = @Translation("Sajari indexing"),
 *   category = @Translation("search")
 * )
 */
class IndexerBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Sitewide configuration for Sajari module.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $sajariConfiguration;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition, $container->get('config.factory')->get('sajari.config')
    );
  }

  /**
   * IndexerBlock constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ImmutableConfig $sajari_configuration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->sajariConfiguration = $sajari_configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#attached' => [
        'library' => ['sajari/sajari.indexer'],
        'drupalSettings' => [
          'sajariIndex' => [
            'project' => $this->sajariConfiguration->get('project'),
            'collection' => $this->sajariConfiguration->get('collection'),
          ],
        ],
      ],
    ];
  }

}
