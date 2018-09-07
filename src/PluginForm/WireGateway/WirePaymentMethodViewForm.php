<?php

namespace Drupal\commerce_wire\PluginForm\WireGateway;

use Drupal\commerce_payment\PluginForm\PaymentGatewayFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Method add form.
 */
class WirePaymentMethodViewForm extends PaymentGatewayFormBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $payment_method = $this->entity->getPaymentMethod();
    $file = $payment_method->commerce_wire_receipt->entity;
    $form['receipt'] = [
      '#theme' => 'image',
      '#uri' => $file->uri->value,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Nothing to do here. Not a real action.
  }

}
