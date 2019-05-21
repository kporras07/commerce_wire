<?php

namespace Drupal\commerce_wire\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce\BundleFieldDefinition;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides the credit card payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "wire_transfer_method",
 *   label = @Translation("Wire Transfer"),
 * )
 */
class WireMethod extends PaymentMethodTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    $file = $payment_method->commerce_wire_receipt->entity;
    $uri = $file->getFileUri();
    $url = file_create_url($uri);

    $url = Url::fromUri($url, $options = [
      'attributes' => [
        'class' => ['img-payment'],
        'target' => ['_blank'],
      ],
    ]);
    $link = Link::fromTextAndUrl($this->t('Receipt: @name', [
      '@name' => $file->getFileName(),
    ]), $url);

    return $link->toString();
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    $fields['commerce_wire_receipt'] = BundleFieldDefinition::create('image')
      ->setLabel(t('Receipt'))
      ->setDescription(t('The receipt of the wire transfer.'))
      ->setRequired(TRUE);

    return $fields;
  }

}
