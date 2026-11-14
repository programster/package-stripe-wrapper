<?php

/*
 * Options for gathering the customer's consent on the checkout page.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-consent_collection
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Interfaces\Arrayable;

final readonly class ConsentConfig implements Arrayable
{
    private bool $m_showTermsOfServiceCheckbox;
    private ?bool $m_hidePaymentMethodReuseAgreement;
    private ?bool $m_hidePromotionsConsent;


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
        bool $showTermsOfServiceCheckbox,
        ?bool $hidePaymentMethodReuseAgreement = null,
        ?bool $hidePromotionsConsent = null
    )
    {
        $this->m_showTermsOfServiceCheckbox = $showTermsOfServiceCheckbox;
        $this->m_hidePaymentMethodReuseAgreement = $hidePaymentMethodReuseAgreement;
        $this->m_hidePromotionsConsent = $hidePromotionsConsent;
    }


    /**
     * @return array<string, mixed>
     */
    public function toArray() : array
    {
        $arrayForm = [
            'terms_of_service' => ($this->m_showTermsOfServiceCheckbox) ? 'required' : 'none'
        ];

        if ($this->m_hidePaymentMethodReuseAgreement !== null)
        {
            $arrayForm["payment_method_reuse_agreement"] = [
                'position' => ($this->m_hidePaymentMethodReuseAgreement) ? 'hidden' : 'auto'
            ];
        }

        if ($this->m_hidePromotionsConsent !== null)
        {
            $arrayForm["promotions"] = ($this->m_hidePromotionsConsent) ? 'none' : 'auto';
        }

        return $arrayForm;
    }
}
