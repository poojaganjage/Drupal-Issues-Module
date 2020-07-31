<?php

namespace Drupal\commerce_fraud\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the fraud event.
 *
 * @see \Drupal\commerce_fraud\Event\FraudEvents
 */
class FraudEvent extends Event {

  /**
   * Count.
   *
   * @var int
   */
  protected $count;

  /**
   * Order id.
   *
   * @var int
   */
  protected $orderId;

  /**
   * Note.
   *
   * @var int
   */
  protected $note;

  /**
   * FraudEvent constructor.
   *
   * @param int $count
   *   Counter.
   * @param int $orderId
   *   Order ID.
   * @param string $note
   *   Note.
   */
  public function __construct($count, $orderId, $note) {
    $this->count = $count;
    $this->orderId = $orderId;
    $this->note = $note;
  }

  /**
   * Return count.
   *
   * @return string
   *   Counter.
   */
  public function getCount() {
    return $this->count;
  }

  /**
   * Return orderId.
   *
   * @return int
   *   Order Id.
   */
  public function getOrderId() {
    return $this->orderId;
  }

  /**
   * Return note.
   *
   * @return int
   *   Note.
   */
  public function getNote() {
    return $this->note;
  }

}
