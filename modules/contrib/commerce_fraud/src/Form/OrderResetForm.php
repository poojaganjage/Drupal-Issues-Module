<?php

namespace Drupal\commerce_fraud\Form;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\ConfirmFormBase;

/**
 * Provides a confirmation form for resetting orders.
 */
class OrderResetForm extends ConfirmFormBase {

  /**
   * The current order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * Database.
   *
   * @var database
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->order = \Drupal::routeMatch()->getParameter('commerce_order');
    $this->database = \Drupal::database();
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
  public function getFormId() {
    return 'commerce_order_reset_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Do you want to reset order fraud score for order %id?', ['%id' => $this->order->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->order->toUrl('collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t("Reset this orders fraud score to 0");
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Reset Fraud Score');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Remove the order fraudulent score.
    $this->database->delete('commerce_fraud_fraud_score')
      ->condition('order_id', $this->order->id())
      ->execute();

    $this->messenger()->addMessage($this->t('The orders score has been reset.'));

    // Redirect to order lists page.
    $form_state->setRedirectUrl($this->order->toUrl('collection'));

  }

}
