<?php


namespace Programster\Stripe\Models;

use Programster\Stripe\Interfaces\Arrayable;

readonly class ConsentConfig implements Arrayable
{
    /**
     * An object for defining various consent options on the checkout page.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-consent_collection
     *
     * @param bool $showTermsOfServiceCheckbox - whether to show a terms of service checkbox that a customer must check
     * before being able to make a payment. If enabled, then you must have a valid terms of service URL set in your
     * Dashboard settings.
     *
     * @param bool $hidePaymentMethodReuseAgreement - whether to hide the payment reuse agreement.
     *
     * @param bool $hidePromotionsConsent - whether to disable the collection of customer consent for promotional
     * communications. If set to false (default), the Checkout Session will determine whether to display an option to
     * opt into promotional communication from the merchant depending on if a customer is provided, and if that customer
     * has consented to receiving promotional communications from the merchant in the past.
     */
    public function __construct(
        private bool $showTermsOfServiceCheckbox,
        private bool $hidePaymentMethodReuseAgreement = false,
        private bool $hidePromotionsConsent = false
    )
    {

    }


    public function toArray(): array
    {
        return [
            "payment_method_reuse_agreement" => [
                'position' => ($this->hidePaymentMethodReuseAgreement) ? 'hidden' : 'auto'
            ],
            'promotions' => ($this->hidePromotionsConsent) ? 'none' : 'auto',
            'terms_of_service' => ($this->showTermsOfServiceCheckbox) ? 'required' : 'none'
        ];
    }
}