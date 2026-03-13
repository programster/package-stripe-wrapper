<?php


namespace Programster\Stripe\Models;

use Programster\Stripe\Interfaces\Arrayable;

readonly class InvoiceSettings implements Arrayable
{

    /**
     * @param AccountReference $issuer - This is only applicable if using Stripe Connect, and is the connected account
     * that issues the invoice. The invoice is presented with the branding and support information of the specified
     * account.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-subscription_data-invoice_settings-issuer
     */
    public function __construct(private AccountReference $issuer)
    {

    }


    public function toArray(): array
    {
        return [
            'issuer' => $this->issuer->toArray(),
        ];
    }
}