<?php

/*
 * A subset of parameters to be passed to PaymentIntent creation for Checkout Sessions in payment mode.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-payment_intent_data
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Enums\CaptureMethod;
use Programster\Stripe\Enums\PaymentIntentSetupFutureUsage;
use Programster\Stripe\Interfaces\Arrayable;


readonly class PaymentIntentData implements Arrayable
{
    private ?int $m_applicationFeeAmount;
    private ?string $m_description;
    private CaptureMethod $m_captureMethod;
    private ?Metadata $m_metadata;
    private ?string $m_onBehalfOfStripeAccountId;
    private ?string $m_receiptEmail;
    private ?PaymentIntentSetupFutureUsage $m_setupFutureUsage;
    private ?ShippingConfig $m_shippingConfig;
    private ?string $m_statementDescriptor;
    private ?string $m_statementDescriptorSuffix;
    private ?TransferData $m_transferData;
    private ?string $m_transferGroup;


    /**
     * Create a subset of parameters to be passed to PaymentIntent creation for Checkout Sessions in payment mode.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-payment_intent_data
     *
     * @param int|null $applicationFeeAmount - The amount of the application fee (if any) that will be requested to be
     * applied to the payment and transferred to the application owner’s Stripe account. The amount of the application
     * fee collected will be capped at the total payment amount. For more information, see the PaymentIntents use case
     * for connected accounts: https://docs.stripe.com/connect/charges
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-payment_intent_data-application_fee_amount
     *
     * @param string|null $description - An arbitrary string attached to the object. Often useful for displaying to
     * users.
     *
     * @param CaptureMethod $captureMethod - Controls when the funds will be captured from the customer’s account.
     *
     * @param Metadata|null $metadata - Set of key-value pairs that you can attach to an object. This can be useful for
     * storing additional information about the object in a structured format. Individual keys can be unset by posting
     * an empty value to them. All keys can be unset by posting an empty value to metadata.
     *
     * @param string|null $onBehalfOfStripeAccountId - The Stripe account ID for which these funds are intended.
     * For details, see the PaymentIntents use case for connected accounts.
     *
     * @param string|null $receiptEmail - Email address that the receipt for the resulting payment will be sent to.
     * If receipt_email is specified for a payment in live mode, a receipt will be sent regardless of your email
     * settings.
     *
     * @param PaymentIntentSetupFutureUsage|null $setupFutureUsage - Indicates that you intend to make future payments
     * with the payment method collected by this Checkout Session.
     * https://docs.stripe.com/payments/payment-intents#future-usage
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-payment_intent_data-setup_future_usage
     *
     * @param ShippingConfig|null $shippingConfig - Shipping information for this payment.
     *
     * @param string|null $statementDescriptor - Text that appears on the customer’s statement as the statement
     * descriptor for a non-card charge. This value overrides the account’s default statement descriptor. For
     * information about requirements, including the 22-character limit, see the Statement Descriptor docs.
     * Setting this value for a card charge returns an error. For card charges, set the statement_descriptor_suffix
     * instead.
     *
     * @param string|null $statementDescriptorSuffix - Provides information about a card charge. Concatenated to the
     * account’s statement descriptor prefix (https://docs.stripe.com/get-started/account/statement-descriptors#static)
     * to form the complete statement descriptor that appears on the customer’s statement.
     *
     * @param TransferData|null $transferData - The parameters used to automatically create a Transfer when the payment
     * succeeds, with its amount in pennies/cents. For more information, see the PaymentIntents use case for connected
     * accounts. https://docs.stripe.com/connect/charges
     *
     * @param string|null $transferGroup - string that identifies the resulting payment as part of a group.
     */
    public function __construct(
        ?int $applicationFeeAmount = null,
        ?string $description = null,
        CaptureMethod $captureMethod = CaptureMethod::AUTOMATIC_ASYNC,
        ?Metadata $metadata = null,
        ?string $onBehalfOfStripeAccountId = null,
        ?string $receiptEmail = null,
        ?PaymentIntentSetupFutureUsage $setupFutureUsage = null,
        ?ShippingConfig $shippingConfig = null,
        ?string $statementDescriptor = null,
        ?string $statementDescriptorSuffix = null,
        ?TransferData $transferData = null,
        ?string $transferGroup = null,
    )
    {
        $this->m_applicationFeeAmount = $applicationFeeAmount;
        $this->m_description = $description;
        $this->m_captureMethod = $captureMethod;
        $this->m_metadata = ($metadata !== null) ? clone $metadata : null;
        $this->m_onBehalfOfStripeAccountId = $onBehalfOfStripeAccountId;
        $this->m_receiptEmail = $receiptEmail;
        $this->m_setupFutureUsage = $setupFutureUsage;
        $this->m_shippingConfig = $shippingConfig;
        $this->m_statementDescriptor = $statementDescriptor;
        $this->m_statementDescriptorSuffix = $statementDescriptorSuffix;
        $this->m_transferData = $transferData;
        $this->m_transferGroup = $transferGroup;
    }


    public function toArray() : array
    {
        $arrayForm = ['capture_method' => $this->m_captureMethod->value];

        if ($this->m_applicationFeeAmount !== null)
        {
            $arrayForm['application_fee_amount'] = $this->m_applicationFeeAmount;
        }

        if ($this->m_description !== null)
        {
            $arrayForm['description'] = $this->m_description;
        }

        if ($this->m_metadata !== null)
        {
            $arrayForm['metadata'] = $this->m_metadata->toStripeArrayForm();
        }

        if ($this->m_onBehalfOfStripeAccountId !== null)
        {
            $arrayForm['on_behalf_of'] = $this->m_onBehalfOfStripeAccountId;
        }

        if ($this->m_receiptEmail !== null)
        {
            $arrayForm['receipt_email'] = $this->m_receiptEmail;
        }

        if ($this->m_setupFutureUsage !== null)
        {
            $arrayForm['setup_future_usage'] = $this->m_setupFutureUsage->value;
        }

        if ($this->m_shippingConfig !== null)
        {
            $arrayForm['shipping'] = $this->m_shippingConfig->toArray();
        }

        if ($this->m_statementDescriptor !== null)
        {
            $arrayForm['statement_descriptor'] = $this->m_statementDescriptor;
        }

        if ($this->m_statementDescriptorSuffix !== null)
        {
            $arrayForm['statement_descriptor_suffix'] = $this->m_statementDescriptorSuffix;
        }

        if ($this->m_transferData !== null)
        {
            $arrayForm['transfer_data'] = $this->m_transferData->toArray();
        }

        if ($this->m_transferGroup !== null)
        {
            $arrayForm['transfer_group'] = $this->m_transferGroup;
        }

        return $arrayForm;
    }
}
