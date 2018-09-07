<?php

namespace Drupal\commerce_wire\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayBase;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the Wire Transfer gateway.
 *
 * @CommercePaymentGateway(
 *   id = "wire_transfer_gateway",
 *   label = "Wire Transfer",
 *   display_label = "Wire Transfer",
 *   forms = {
 *     "add-payment-method" = "Drupal\commerce_wire\PluginForm\WireGateway\WirePaymentMethodAddForm",
 *   },
 *   modes = {
 *     "n/a" = @Translation("N/A")
 *   },
 *   payment_method_types = {"wire_transfer_method"}
 * )
 */
class WireGateway extends PaymentGatewayBase implements OnsitePaymentGatewayInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'instructions' => [
        'value' => '',
        'format' => 'plain_text',
      ],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['instructions'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Payment instructions'),
      '#description' => $this->t('Shown the end of checkout, after the customer has placed their order.'),
      '#default_value' => $this->configuration['instructions']['value'],
      '#format' => $this->configuration['instructions']['format'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['instructions'] = $values['instructions'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaymentInstructions() {
    $instructions = [];
    if (!empty($this->configuration['instructions']['value'])) {
      $instructions = [
        '#type' => 'processed_text',
        '#text' => $this->configuration['instructions']['value'],
        '#format' => $this->configuration['instructions']['format'],
      ];
    }

    return $instructions;
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaymentOperations(PaymentInterface $payment) {
    $payment_state = $payment->getState()->value;
    $operations = [];
    $operations['receive'] = [
      'title' => $this->t('Receive'),
      'page_title' => $this->t('Receive payment'),
      'plugin_form' => 'receive-payment',
      'access' => $payment_state == 'pending',
    ];
    $operations['void'] = [
      'title' => $this->t('Void'),
      'page_title' => $this->t('Void payment'),
      'plugin_form' => 'void-payment',
      'access' => $payment_state == 'pending',
    ];
    $operations['refund'] = [
      'title' => $this->t('Refund'),
      'page_title' => $this->t('Refund payment'),
      'plugin_form' => 'refund-payment',
      'access' => in_array($payment_state, ['completed', 'partially_refunded']),
    ];

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function createPaymentMethod(PaymentMethodInterface $payment_method, array $payment_details) {
    if (empty($payment_details['commerce_wire_receipt'])) {
      // @TODO: Better exception handling.
      throw new \Exception('Payment details should contain receipt.');
    }
    else {
      $payment_method->commerce_wire_receipt = $payment_details['commerce_wire_receipt'];
      $payment_method->setRemoteId(0);
      $payment_method->setReusable(FALSE);
      $payment_method->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deletePaymentMethod(PaymentMethodInterface $payment_method) {
    $payment_method->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function createPayment(PaymentInterface $payment, $received = FALSE) {
    $this->assertPaymentState($payment, ['new']);

    $payment->state = $received ? 'completed' : 'pending';
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function receivePayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['pending']);

    // If not specified, use the entire amount.
    $amount = $amount ?: $payment->getAmount();
    $payment->state = 'completed';
    $payment->setAmount($amount);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function voidPayment(PaymentInterface $payment) {
    $this->assertPaymentState($payment, ['pending']);

    $payment->state = 'voided';
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['completed', 'partially_refunded']);
    // If not specified, refund the entire amount.
    $amount = $amount ?: $payment->getAmount();
    $this->assertRefundAmount($payment, $amount);

    $old_refunded_amount = $payment->getRefundedAmount();
    $new_refunded_amount = $old_refunded_amount->add($amount);
    if ($new_refunded_amount->lessThan($payment->getAmount())) {
      $payment->state = 'partially_refunded';
    }
    else {
      $payment->state = 'refunded';
    }

    $payment->setRefundedAmount($new_refunded_amount);
    $payment->save();
  }

}