<?php

/*
 * Intended as a configuration for paying a percentage of a subscription to another ("Stripe Connect") account.
 */

namespace Programster\Stripe\Models;


/**
 * @deprecated - this can't be created, as its constructor is private and it has no static create method, and its
 * getParams() doesn't reliably produce what Stripe needs. It is only kept so that existing code that refers to it
 * keeps working, and will be redesigned in the next major version.
 */
readonly class StripeConnectSubscriptionConfig
{
    private float $m_amountPercentToOtherAccount;
    private string $m_otherStripeAccountId;
    private bool $m_isOnOtherAccountsBehalf;
    private bool $m_issuerIsSelf;


    /**
     * Configure a connected account that you wish to get a percentage of the payment. This may be a case of you being a
     * platform that facilitates suppliers providing a service to their customers, in which case the transaction is
     * "on behalf of" the supplier, so the checkout experience should show the person paying that this payment is going
     * towards that supplier. Alternatively, you might be running a company that sells t-shirts, and every time an order
     * goes through, you need to send a percentage of the payment to your t-shirt supplier to pay for the product to
     * be shipped. The customer paying the checkout only needs to see your organization's details, and not the details
     * of the organization that is actually supplying and shipping the t-shirts.
     *
     * @param float $amountPercentToOtherAccount - the amount of the payment that should go to the other account.
     *
     * @param string $otherStripeAccountId - the ID of the other stripe account.
     *
     * @param bool $isOnOtherAccountsBehalf - whether this payment is on the other account's behalf (you are a platform
     * taking a small fee), or whether the other account just needs paying a percentage, and the person paying doesn't
     * need to know anything about it.
     *
     * @param bool $issuerIsSelf - for invoices, should the issuer appear as yourself, or the other
     * account? This affects the branding of the invoice. It is valid for $issuerIsSelf and $isOnOtherAccountsBehalf to
     * both be set to false, but this would be unusual.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-subscription_data-invoice_settings-issuer
     */
    private function __construct(
        float $amountPercentToOtherAccount,
        string $otherStripeAccountId,
        bool $isOnOtherAccountsBehalf,
        bool $issuerIsSelf
    )
    {
        $this->m_amountPercentToOtherAccount = $amountPercentToOtherAccount;
        $this->m_otherStripeAccountId = $otherStripeAccountId;
        $this->m_isOnOtherAccountsBehalf = $isOnOtherAccountsBehalf;
        $this->m_issuerIsSelf = $issuerIsSelf;
    }


    /**
     * @deprecated - see the class's docblock.
     * @return array
     */
    public function getParams() : array
    {
        $params = [];

        if ($this->m_isOnOtherAccountsBehalf)
        {
            $params['on_behalf_of'] = $this->m_otherStripeAccountId;
            $params['application_fee_percent'] = (100 - $this->m_amountPercentToOtherAccount);
        }
        else
        {
            $params['transfer_data']['amount_percent'] = $this->m_amountPercentToOtherAccount;
            $params['transfer_data']['destination'] = $this->m_otherStripeAccountId;
        }

        return $params;
    }


    /**
     * @deprecated - see the class's docblock.
     * @return AccountReference
     */
    public function getIssuerObj() : AccountReference
    {
        $issuer = ($this->m_issuerIsSelf)
            ? AccountReference::createSelf()
            : AccountReference::createRelatedAccount($this->m_otherStripeAccountId);

        return $issuer;
    }
}
