<?php

namespace Drupal\commerce_fraud\Event;

/**
 * Class FraudEvents.
 *
 * @package Drupal\commerce_fraud\Event
 */
final class FraudEvents {

  /**
   * Name of the event fired when a new FraudEvent is reported.
   *
   * @Event
   * @see \Drupal\commerce_fraud\Event\FraudEvent
   * @var string
   */
  const FRAUD_COUNT_INSERT = 'commerce_fraud.fraud_count_insert';

  const FRAUD_COUNT_UPDATED = 'commerce_fraud.fraud_count_updated';

}
