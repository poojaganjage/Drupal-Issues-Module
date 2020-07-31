<?php

namespace Drupal\Tests\commerce_fraud\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\commerce_order\Entity\Order;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\commerce_fraud\EventSubscriber\CommerceFraudSubscriber;
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


/**
 * @coversDefaultClass \Drupal\commerce_fraud\EventSubscriber\CommerceFraudSubscriber
 * @group commerce
 */
class CommerceFraudSubscriberTest extends UnitTestCase {

  /**
   * The test order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $dispatcher = $this->prophesize(EventDispatcherInterface::class);
    $messenger = $this->prophesize(MessengerInterface::class);
    $connection = $this->prophesize(Connection::class);
    $config = $this->prophesize(ConfigFactoryInterface::class);
    $config->get('system.site')->willReturn('SiteName');
    // $config->get('name')->willReturn('SiteName');

    $this->subscriber = new CommerceFraudSubscriber($dispatcher->reveal(), $messenger->reveal(), $connection->reveal(), $config->reveal());
  }

  /**
   * @covers ::getMailParams
   */
  public function testMailParameters() {
    // $this->order = new Order([
    //   'type' => 'default',
    //   'state' => 'completed',
    //   'mail' => 'test@example.com',
    //   'ip_address' => '127.0.0.1',
    //   'order_number' => '6',
    //   'uid' => 1,
    //   'store_id' => 1,
    //   'order_items' => [],
    // ]);
    $order = $this->createMock(Order::class);

    $params = $this->subscriber->getMailParams($order, 20);
    // $this->assertIsArray($types);
    // foreach ($types as $key => $type) {
    //   $this->assertInstanceOf(CreditCardType::class, $type);
    //   $this->assertEquals($key, $type->getId());
    // }
  }

}