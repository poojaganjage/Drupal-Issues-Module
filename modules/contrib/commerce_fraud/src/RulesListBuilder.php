<?php

namespace Drupal\commerce_fraud;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Builds a listing of Rules entities.
 *
 * @see \Drupal\commerce_fraud\Entity\Rules
 */
class RulesListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function load() {

    $entities = [
      'enabled' => [],
      'disabled' => [],
    ];

    // Sort entities into enabled and disabled.
    foreach (parent::load() as $entity) {

      if ($entity->get('status')) {
        $entities['enabled'][] = $entity;
      }
      else {
        $entities['disabled'][] = $entity;
      }

    }

    return $entities;

  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {

    $header['id'] = $this->t('Rules ID');
    $header['name'] = $this->t('Name');
    $header['rule_name'] = $this->t('Rule name');
    $header['counter'] = $this->t('Counter');

    return $header + parent::buildHeader();

  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {

    /* @var \Drupal\commerce_fraud\Entity\RulesInterface $entity */
    $row['label'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.rules.edit_form',
      ['rules' => $entity->id()]
    );
    $row['rule_name'] = $entity->getPlugin()->getLabel();
    $row['counter'] = $entity->getCounter();

    return $row + parent::buildRow($entity);

  }

  /**
   * {@inheritdoc}
   */
  public function render() {

    // Set up the headers and tables.
    $list = [
      'enabled' => [
        'heading' => [
          '#markup' => '<h2>' . $this->t('Enabled') . '</h2>',
        ],
        'table' => [
          '#type' => 'table',
          '#empty' => $this->t('There are no enabled rules.'),
        ],
      ],
      'disabled' => [
        'heading' => [
          '#markup' => '<h2>' . $this->t('Disabled') . '</h2>',
        ],
        'table' => [
          '#type' => 'table',
          '#empty' => $this->t('There are no disabled rules.'),
        ],
      ],
    ];

    $entities = $this->load();

    foreach (['enabled', 'disabled'] as $status) {

      $list[$status]['table'] += [
        '#header' => $this->buildHeader(),
      ];

      foreach ($entities[$status] as $entity) {
        $list[$status]['table']['#rows'][$entity->id()] = $this->buildRow($entity);
      }

    }

    return $list;

  }

}
