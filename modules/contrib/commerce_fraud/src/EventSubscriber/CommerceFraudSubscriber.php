<?php

namespace Drupal\commerce_fraud\EventSubscriber;

use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\commerce_fraud\Event\FraudEvents;
use Drupal\commerce_fraud\Event\FraudEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Event subscriber, that acts on the place transition of commerce order
 * entities, in order to set fraud score.
 */
class CommerceFraudSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * System site configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * If order is stopped from completing.
   *
   * @var bool
   */
  protected $orderStopped;

  /**
   * The log storage.
   *
   * @var \Drupal\commerce_log\LogStorageInterface
   */
  protected $logStorage;

  /**
   * Constructs a new FraudSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to be used.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher, MessengerInterface $messenger, Connection $connection, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logStorage = $this->entityTypeManager->getStorage('commerce_log');
    $this->eventDispatcher = $event_dispatcher;
    $this->connection = $connection;
    $this->messenger = $messenger;
    $this->config = $config_factory->get('system.site');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('event_dispatcher'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      'commerce_order.place.pre_transition' => ['setFraudScore'],
    ];
    return $events;
  }

  /**
   * Sets the Fraud score on placing the order.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function setFraudScore(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    // Get Order.
    $order = $event->getEntity();

    // Get Rules.
    $rules = \Drupal::entityTypeManager()->getStorage('rules');

    // Load Rules.
    foreach ($rules->loadMultiple() as $rule) {

      // Apply the rule.
      // File contating apply function is plugin-fraud rule.
      $action = $rule->getPlugin()->apply($order);

      // Check if the rule applied.
      if (!$action) {
        continue;
      }

      // Get the counter and name set in the entity.
      $fraud_count = $rule->getCounter();
      $rule_name = $rule->getPLugin()->getLabel();

      // Add a log to order activity.
      $this->logStorage->generate($order, 'fraud_rule_name', ['rule_name' => $rule_name])->save();

      // Detail for Fraud Event.
      $note = $rule_name . ": " . $fraud_count;
      $event = new FraudEvent($fraud_count, $order->id(), $note);

      // Dispatch Fraud Event with inserting event.
      $this->eventDispatcher->dispatch(FraudEvents::FRAUD_COUNT_INSERT, $event);
    }

    // Calculating complete fraud score for the order.
    $updated_fraud_score = $this->getFraudScore($order->id());
    $this->getFraudRules($order->id());

    // Compare order fraud score with block list cap set in settings.
    if ($updated_fraud_score <= \Drupal::state()->get('commerce_fraud_blocklist_cap', 20)) {
      return;
    }

    $this->orderStopped = FALSE;

    // Cancel order if set in settings.
    if (\Drupal::state()->get('stop_order', FALSE)) {
      $this->cancelFraudulentOrder($order);
      $this->orderStopped = TRUE;
    }

    // Sending the details of the blocklisted order via mail.
    $this->sendBlockListedOrderMail($order, $updated_fraud_score);

  }

  /**
   * Returns the fraud score as per order id.
   *
   * @param int $order_id
   *   Order Id.
   *
   * @return int
   *   Fraud Score.
   */
  public function getFraudScore(int $order_id) {
    // Query to get all fraud score for order id.
    $query = $this->connection->select('commerce_fraud_fraud_score');
    $query->condition('order_id', $order_id);
    $query->addExpression('SUM(fraud_score)', 'fraud');
    $result = $query->execute()->fetchCol();

    return $result[0] ?? 0;
  }

  /**
   * Returns name of fraud rules that applied on order.
   *
   * @param int $order_id
   *   Order Id.
   *
   * @return array
   *   Name of fraud rules that applied on order.
   */
  public function getFraudRules(int $order_id) {

    $query = $this->connection->select('commerce_fraud_fraud_score', 'n');
    $query->fields('n', ['note']);
    $query->condition('order_id', $order_id);
    $result = $query->execute()->fetchAllAssoc('note', \PDO::FETCH_ASSOC);

    return $result;
  }

  /**
   * Cancels the order and sets its status to fradulent.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   Order.
   */
  public function cancelFraudulentOrder(OrderInterface $order) {
    // Cancelling the order and setting the status to fraudulent.
    $order->getState()->applyTransitionById('cancel');
    $order->getState()->setValue(['value' => 'fraudulent']);

    // Creating of log for the order and refreshing it on load.
    $this->logStorage->generate($order, 'order_fraud')->save();
    $order->setRefreshState(OrderInterface::REFRESH_ON_LOAD);
    $this->messenger->addWarning($this->t('This order is suspected to be
      fraudulent and cannot be completed. Contact the administrators for more
      info and help.'));
  }

  /**
   * Sends email about blocklisted orders to the email choosen un settings.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   Order.
   * @param int $fraud_score
   *   Fraud Score.
   */
  public function sendBlockListedOrderMail(OrderInterface $order, int $fraud_score) {

    $mailManager = \Drupal::service('plugin.manager.mail');

    // Mail details.
    $module = 'commerce_fraud';
    $key = 'send_blocklist';
    $to = \Drupal::state()->get('send_email', $this->config->get('mail'));
    // Mail message.
    $params['message'] = $this->getMailParamsForBlocklist($order, $fraud_score);
    $params['order_id'] = $order->id();
    $langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
    $send = TRUE;

    $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);

  }

  /**
   * Return message with details about order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   Order.
   * @param int $fraud_score
   *   Fraud Score.
   *
   * @return array
   *   Array of parameters to be used in mail.
   *   Possible keys:
   *   - sitename: Name of site.
   *   - order_id: Order ID.
   *   - user_id: User id
   *   - status: Current Order status.
   *   - placed: When was the order placed in m/d/y format.
   *   - fraud_score: Fraud score of order.
   *   - stopped: Bool to check if order is allowed to be completed.
   *   - fraud_notes: List of name of fraud rules that applied to order.
   */
   public function getMailParamsForBlocklist(OrderInterface $order, int $fraud_score) {
    return [
      'sitename' => $this->config->get('name'),
      'order_id' => $order->id(),
      'user_id' => $order->getCustomerId(),
      'user_name' => $order->getCustomer()->getDisplayName(),
      'status' => $order->getState()->getId(),
      'placed' => date('m/d/Y H:i:s', $order->getPlacedTime()),
      'fraud_score' => $fraud_score,
      'stopped' => $this->orderStopped,
      'fraud_notes' => $this->getFraudRules($order->id()),
    ];
  }

}
