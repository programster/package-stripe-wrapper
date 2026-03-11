<?php



namespace Programster\Stripe\Models;

use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Enums\CaptureMethod;
use Programster\Stripe\Enums\PaymentIntentSetupFutureUsage;
use Programster\Stripe\Interfaces\Arrayable;

readonly class PaymentIntentData implements Arrayable
{
    /**
     * Create a subset of parameters to be passed to PaymentIntent creation for Checkout Sessions in payment mode.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-payment_intent_data
     *
     * @param int $applicationFeeAmount - The amount of the application fee (if any) that will be requested to be
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
     * succeeds. For more information, see the PaymentIntents use case for connected accounts.
     * https://docs.stripe.com/connect/charges
     *
     * @param string|null $transferGroup - string that identifies the resulting payment as part of a group.
     */
    public function __construct(
        private ?int $applicationFeeAmount = null,
        private ?string $description = null,
        private CaptureMethod $captureMethod = CaptureMethod::AUTOMATIC_ASYNC,
        private ?Metadata $metadata = null,
        private ?string $onBehalfOfStripeAccountId = null,
        private ?string $receiptEmail = null,
        private ?PaymentIntentSetupFutureUsage $setupFutureUsage = null,
        private ?ShippingConfig $shippingConfig = null,
        private ?string $statementDescriptor = null,
        private ?string $statementDescriptorSuffix = null,
        private ?TransferData $transferData = null,
        private ?string $transferGroup = null,
    )
    {

    }


    public function toArray(): array
    {
        $arrayForm = [
            'capture_method' => $this->captureMethod->value,
        ];

        if ($this->applicationFeeAmount !== null)
        {
            $arrayForm['application_fee_amount'] = $this->applicationFeeAmount;
        }

        if ($this->description !== null)
        {
            $arrayForm['description'] = $this->description;
        }

        if ($this->metadata !== null)
        {
            $arrayForm['metadata'] = $this->metadata->toStripeArrayForm();
        }

        if ($this->onBehalfOfStripeAccountId !== null)
        {
            $arrayForm['on_behalf_of'] = $this->onBehalfOfStripeAccountId;
        }

        if ($this->receiptEmail !== null)
        {
            $arrayForm['receipt_email'] = $this->receiptEmail;
        }

        if ($this->setupFutureUsage !== null)
        {
            $arrayForm['setup_future_usage'] = $this->setupFutureUsage->value;
        }

        if ($this->shippingConfig !== null)
        {
            $arrayForm['shipping'] = $this->shippingConfig->toArray();
        }

        if ($this->statementDescriptor !== null)
        {
            $arrayForm['statement_descriptor'] = $this->statementDescriptor;
        }

        if ($this->statementDescriptorSuffix !== null)
        {
            $arrayForm['statement_descriptor_suffix'] = $this->statementDescriptorSuffix;
        }

        if ($this->transferData !== null)
        {
            $arrayForm['transfer_data'] = $this->transferData->toArray();
        }

        if ($this->transferGroup !== null)
        {
            $arrayForm['transfer_group'] = $this->transferGroup;
        }

        return $arrayForm;
    }
}