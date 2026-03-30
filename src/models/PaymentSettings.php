<?php

/*
 * An object to reference self or another account. This can be used for providing information about tax liability or
 * issuer data for a "Stripe Connect" invoice.
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Collections\PaymentMethodTypeCollection;
use Programster\Stripe\Enums\PaymentMethodType;
use Programster\Stripe\Interfaces\Arrayable;

readonly class PaymentSettings implements Arrayable
{
    /**
     * Payment settings to pass to invoices created by the subscription.
     *
     * @param array|null $paymentMethodOptions - Payment-method-specific configuration to provide to invoices created
     * by the subscription.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-payment_settings-payment_method_options
     *
     * @param PaymentMethodTypeCollection|null $paymentMethodTypes - The list of payment method types (e.g. card) to
     * provide to the invoice’s PaymentIntent. If not set, Stripe attempts to automatically determine the types to use
     * by looking at the invoice’s default payment method, the subscription’s default payment method, the customer’s
     * default payment method, and your invoice template settings. Should not be specified with
     * payment_method_configuration
     *
     * @param bool|null $saveDefaultPaymentMethod - Configure whether Stripe updates subscription.default_payment_method
     * when payment succeeds. Defaults to off if unspecified. If set to true, then will tell Stripe "on_subscription"
     * so Stripe sets subscription.default_payment_method when a subscription payment succeeds.
     */
    public function __construct(
        private ?array $paymentMethodOptions = null,
        private ?PaymentMethodTypeCollection $paymentMethodTypes = null,
        private ?bool $saveDefaultPaymentMethod = null,
    )
    {
    }


    public function toArray(): array
    {
        $arrayForm = [];

        if ($this->paymentMethodOptions !== null)
        {
            $arrayForm['payment_method_options'] = $this->paymentMethodOptions;
        }

        if ($this->paymentMethodTypes !== null)
        {
            $arrayForm['payment_method_types'] = $this->paymentMethodTypes->toStripeArrayForm();
        }

        if ($this->saveDefaultPaymentMethod !== null)
        {
            $arrayForm['save_default_payment_method'] = $this->saveDefaultPaymentMethod;
        }

        return $arrayForm;
    }
}