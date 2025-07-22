<?php

/*
 * An object to reference self or another account. This can be used for providing information about tax liability or
 * issuer data for a "Stripe Connect" invoice.
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Interfaces\Arrayable;

readonly class StripeConnectSubscriptionConfig
{
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
        private float $amountPercentToOtherAccount,
        private string $otherStripeAccountId,
        private bool $isOnOtherAccountsBehalf,
        private bool $issuerIsSelf
    )
    {

    }


    public function getParams(): array
    {
        $params = [];

        if ($this->isOnOtherAccountsBehalf)
        {
            $params['on_behalf_of'] = $this->otherStripeAccountId;
            $params['application_fee_percent'] = (100 - $this->amountPercentToOtherAccount);
        }
        else
        {
            $params['transfer_data']['amount_percent'] = $this->amountPercentToOtherAccount;
            $params['transfer_data']['destination'] = $this->otherStripeAccountId;
        }

        return $params;
    }

    public function getIssuerObj()
    {
        return ($this->issuerIsSelf) ? AccountReference::createSelf() : AccountReference::createRelatedAccount($this->otherStripeAccountId);
    }
}