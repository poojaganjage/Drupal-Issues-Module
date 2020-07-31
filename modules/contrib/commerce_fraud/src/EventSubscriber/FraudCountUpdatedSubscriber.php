<?php

namespace Drupal\commerce_fraud\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\commerce_fraud\Event\FraudEvent;
use Drupal\Core\Database\Connection;

/**
 * Modifies the fraud score of orders.
 */
class FraudCountUpdatedSubscriber implements EventSubscriberInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new FraudCountSubscriber object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to be used.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      'commerce_fraud.fraud_count_insert' => ['addFraudCount'],
      'commerce_fraud.fraud_count_update' => ['changeFraudCount'],
    ];
    return $events;
  }

  /**
   * Inserts the fraud score.
   *
   * @param \Drupal\commerce_fraud\Event\FraudEvent $event
   *   FraudEvent class object.
   */
  public function addFraudCount(FraudEvent $event) {

    $fields = [
      'fraud_score' => $event->getCount(),
      'order_id' => $event->getOrderId(),
      'note' => $event->getNote(),
    ];

    $this->connection->insert('commerce_fraud_fraud_score')
      ->fields($fields)
      ->execute();
  }

  /**
   * Updates the fraud score.
   *
   * @param \Drupal\commerce_fraud\Event\FraudEvent $event
   *   FraudEvent object.
   */
  public function changeFraudCount(FraudEvent $event) {

    $fields = [
      'fraud_score' => $event->getCount(),
      'order_id' => $event->getOrderId(),
      'note' => $event->getNote(),
    ];
    $this->connection->update('commerce_fraud_fraud_score')
      ->fields($fields)
      ->condition('order_id', $event->getOrderId())
      ->execute();
  }

}
