<?php

namespace Drupal\commerce_wire\PluginForm\WireGateway;

use Drupal\commerce_payment\PluginForm\PaymentMethodAddForm as BasePaymentMethodAddForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Method add form.
 */
class WirePaymentMethodAddForm extends BasePaymentMethodAddForm {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['payment_details']['commerce_wire_receipt'] = $this->buildWireTransferForm($form['payment_details'], $form_state);
    $form['payment_details']['instructions'] = $this->entity->getPaymentGateway()->getPlugin()->buildPaymentInstructions();
    $form_state->setCached(FALSE);
    $form_state->disableCache();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildWireTransferForm(array $element, FormStateInterface $form_state) {
    $element = [
      '#title' => $this->t('Receipt'),
      '#type' => 'managed_file',
      '#description' => $this->t("Allowed formats: jpg, jpeg, png"),
      '#upload_location' => 'public://receipt/',
      '#upload_validators' => [
        'file_validate_extensions' => ['jpg jpeg png'],
        'file_validate_size' => [5 * 1024 * 1024],
      ],
      '#required' => TRUE,
    ];
    return $element;
  }

}
