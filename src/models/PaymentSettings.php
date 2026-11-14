<?php

/*
 * Payment settings to pass to invoices created by a subscription.
 * https://docs.stripe.com/api/subscriptions/create#create_subscription-payment_settings
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Collections\PaymentMethodTypeCollection;
use Programster\Stripe\Interfaces\Arrayable;


readonly class PaymentSettings implements Arrayable
{
    private ?array $m_paymentMethodOptions;
    private ?PaymentMethodTypeCollection $m_paymentMethodTypes;
    private ?bool $m_saveDefaultPaymentMethod;


    /**
     * Payment settings to pass to invoices created by the subscription.
     *
     * @param array|null $paymentMethodOptions - Payment-method-specific configuration to provide to invoices created
     * by the subscription, in the array form Stripe takes.
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
        ?array $paymentMethodOptions = null,
        ?PaymentMethodTypeCollection $paymentMethodTypes = null,
        ?bool $saveDefaultPaymentMethod = null,
    )
    {
        $this->m_paymentMethodOptions = $paymentMethodOptions;
        $this->m_paymentMethodTypes = ($paymentMethodTypes !== null) ? clone $paymentMethodTypes : null;
        $this->m_saveDefaultPaymentMethod = $saveDefaultPaymentMethod;
    }


    public function toArray() : array
    {
        $arrayForm = [];

        if ($this->m_paymentMethodOptions !== null)
        {
            $arrayForm['payment_method_options'] = $this->m_paymentMethodOptions;
        }

        if ($this->m_paymentMethodTypes !== null)
        {
            $arrayForm['payment_method_types'] = $this->m_paymentMethodTypes->toStripeArrayForm();
        }

        if ($this->m_saveDefaultPaymentMethod !== null)
        {
            $arrayForm['save_default_payment_method'] = ($this->m_saveDefaultPaymentMethod) ? 'on_subscription' : 'off';
        }

        return $arrayForm;
    }
}
