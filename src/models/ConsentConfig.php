<?php


namespace Programster\Stripe\Models;

use Programster\Stripe\Interfaces\Arrayable;

readonly class ConsentConfig implements Arrayable
{
    /**
     * An object for defining various consent options on the checkout page.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-consent_collection
     * Please note that the docs made it look like $hidePaymentMethodReuseAgreement and $hidePromotionsConsent needed
     * to be set if this object exists, and if so defaulted to false, but I found that doing so would cause an error
     * for me saying promotions were not available in my country, which was resolved by not providing it at all instead
     * of specifying it as false. Thus, this object defaults to null for these, so one can still manually specify that
     * one needs the terms of service to be agreed to, without worrying about promotions etc.
     *
     * @param bool $showTermsOfServiceCheckbox - whether to show a terms of service checkbox that a customer must check
     * before being able to make a payment. If enabled, then you must have a valid terms of service URL set in your
     * Dashboard settings.
     *
     * @param ?bool $hidePaymentMethodReuseAgreement - whether to hide the payment reuse agreement.
     *
     * @param ?bool $hidePromotionsConsent - whether to disable the collection of customer consent for promotional
     * communications. If set to false, the Checkout Session will determine whether to display an option to
     * opt into promotional communication from the merchant depending on if a customer is provided, and if that customer
     * has consented to receiving promotional communications from the merchant in the past.
     */
    public function __construct(
        private bool $showTermsOfServiceCheckbox,
        private ?bool $hidePaymentMethodReuseAgreement = null,
        private ?bool $hidePromotionsConsent = null
    )
    {

    }


    public function toArray(): array
    {
        $arrayForm = [
            'terms_of_service' => ($this->showTermsOfServiceCheckbox) ? 'required' : 'none'
        ];

        if ($this->hidePaymentMethodReuseAgreement !== null)
        {
            $arrayForm["payment_method_reuse_agreement"] = [
                'position' => ($this->hidePaymentMethodReuseAgreement) ? 'hidden' : 'auto'
            ];
        }

        if ($this->hidePromotionsConsent !== null)
        {
            $arrayForm["promotions"] = ($this->hidePromotionsConsent) ? 'none' : 'auto';
        }

        return $arrayForm;
    }
}