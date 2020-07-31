<?php

namespace Drupal\Tests\commerce_fraud\Kernel\EventSubscriber;

use Drupal\commerce_order\Entity\Order;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;
use Drupal\Core\Database\Database;

/**
 * Tests the CommerceFraudSubscriber class.
 *
 * @coversDefaultClass \Drupal\commerce_fraud\EventSubscriber\CommerceFraudSubscriber
 *
 * @group commerce
 */
class CommerceFraudSubscriberUnitTest extends OrderKernelTestBase {

  /**
   * System site configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * {@inheritDoc}
   */
  public static $modules = [
    'commerce_log',
    'commerce_fraud',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {

    parent::setUp();

    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_log');
    $this->installEntitySchema('rules');
    $this->installConfig(['commerce_fraud']);
    $this->installSchema('commerce_fraud', ['commerce_fraud_fraud_score']);

    $config_factory = $this->container->get('config.factory');

    $editConfig = $config_factory->getEditable('system.site');
    $editConfig->set('name', 'SiteName');
    $editConfig->save();

    $this->config = $config_factory->get('system.site');

    $this->commerceFraudSubscriber = $this->container->get('commerce_fraud.commerce_fraud_subscriber');

    $user = $this->createUser();

    /** @var \Drupal\commerce_order\Entity\Order order */
    $this->order = Order::create([
      'type' => 'default',
      'state' => 'draft',
      'mail' => 'test@example.com',
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'uid' => $user,
      'store_id' => $this->store,
      'order_items' => [],
    ]);
    $this->order->save();

  }

  /**
   * Tests getMailParamsForBlocklisted function in CommerceFraudSubscriber.
   */
  public function testGetMailParamsForBlocklisted() {

    $fields = [
      'fraud_score' => 20,
      'order_id' => $this->order->id(),
      'note' => 'Name of fraud rule: 20',
    ];

    Database::getConnection()->insert('commerce_fraud_fraud_score')
      ->fields($fields)
      ->execute();

    $fields = [
      'fraud_score' => 20,
      'order_id' => 5,
      'note' => 'Name of fraud rule: 20',
    ];

    Database::getConnection()->insert('commerce_fraud_fraud_score')
      ->fields($fields)
      ->execute();

    $params = $this->commerceFraudSubscriber->getMailParamsForBlocklist($this->order,20);

    $this->assertEqual($params['order_id'], $this->order->id());
    $this->assertEqual($params['user_id'], $this->order->getCustomerId());
    $this->assertEqual($params['user_name'], $this->order->getCustomer()->getDisplayName());
    $this->assertEqual($params['status'], 'draft');
    $this->assertEqual($params['fraud_score'], 20);
    $this->assertEqual($params['stopped'], FALSE);

    $fraud_note =
    [
      "Name of fraud rule: 20" => [
        "note" =>  "Name of fraud rule: 20",
      ]
    ];
    $this->assertEqual($params['fraud_notes'], $fraud_note);

    foreach ($params['fraud_notes'] as $rules) {
      $this->assertEqual($rules['note'], 'Name of fraud rule: 20');
    }

    $this->assertEqual($params['sitename'], 'SiteName');

  }

}