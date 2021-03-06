<?php

namespace Drupal\Tests\commerce_fraud\Kernel\EventSubscriber;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_fraud\Entity\Rules;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Connection;
use Drupal\user\Entity\User;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\commerce_fraud\EventSubscriber\CommerceFraudSubscriber;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\commerce_fraud\Event\FraudEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Tests the CommerceFraudSubscriber class.
 *
 * @coversDefaultClass \Drupal\commerce_fraud\EventSubscriber\CommerceFraudSubscriber
 *
 * @group commerce
 */
class CommerceFraudSubscriberTest extends OrderKernelTestBase {

  /**
   * The test order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

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

    $rule1 = Rules::create([
      'id' => 'example',
      'label' => 'ANONYMOUS',
      'status' => TRUE,
      'plugin' => 'anonymous_user',
      'counter' => 9,
    ]);

    $rule1->save();
    $rule2 = Rules::create([
      'id' => 'example_2',
      'label' => 'ANONYMOUS2',
      'status' => TRUE,
      'plugin' => 'anonymous_user',
      'counter' => 13,
    ]);

    $rule2->save();

    $dispatcher = $this->prophesize(EventDispatcherInterface::class);
    $messenger = $this->prophesize(MessengerInterface::class);
    $connection = $this->prophesize(Connection::class);
    $config = $this->prophesize(ConfigFactoryInterface::class);
    $config->get('system.site')->willReturn('SiteName');

    $this->subscriber = new CommerceFraudSubscriber($dispatcher->reveal(), $messenger->reveal(), $connection->reveal(), $config->reveal());

  }

  /**
   * Tests setting the order number on place transition.
   */
  public function testSetFraudScore() {

    $user = User::getAnonymousUser();

    /** @var \Drupal\commerce_order\Entity\Order $order1 */
    $order1 = Order::create([
      'type' => 'default',
      'state' => 'draft',
      'mail' => 'test@example.com',
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'uid' => $user,
      'store_id' => $this->store,
      'order_items' => [],
    ]);
    $order1->save();

    $transition = $order1->getState()->getTransitions();
    $order1->getState()->applyTransition($transition['place']);
    $order1->save();

    // Query to get all fraud score for order id.
    $query = Database::getConnection()->select('commerce_fraud_fraud_score');
    $query->condition('order_id', $order1->id());
    $query->addExpression('SUM(fraud_score)', 'fraud');
    $result = $query->execute()->fetchCol();

    $this->assertEquals(22, $result[0]);

    $this->assertEquals('completed', $order1->getState()->getId());

    \Drupal::state()->set('stop_order', TRUE);

    $order2 = Order::create([
      'type' => 'default',
      'state' => 'draft',
      'mail' => 'test@example.com',
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'uid' => $user,
      'store_id' => $this->store,
      'order_items' => [],
    ]);
    $order2->save();

    $transition = $order2->getState()->getTransitions();
    $order2->getState()->applyTransition($transition['place']);
    $order2->save();

    // Query to get all fraud score for order id.
    $query = Database::getConnection()->select('commerce_fraud_fraud_score');
    $query->condition('order_id', $order2->id());
    $query->addExpression('SUM(fraud_score)', 'fraud');
    $result = $query->execute()->fetchCol();

    $this->assertEquals(22, $result[0]);

    $this->assertEquals('fraudulent', $order2->getState()->getId());

  }

  /**
   * @covers ::getMailParamsForBlocklist
   */
  public function testMailParameters() {

    $order = $this->createMock(Order::class);

    $params = $this->subscriber->getMailParamsForBlocklist($order, 20);
  }

}
