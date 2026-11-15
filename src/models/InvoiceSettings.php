<?php

/*
 * The settings that all of a subscription's invoices are billed with.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-subscription_data-invoice_settings
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Interfaces\Arrayable;

/**
 * @phpstan-import-type AccountReferenceArray from AccountReference
 */
final readonly class InvoiceSettings implements Arrayable
{
    private AccountReference $m_issuer;


    /**
     * @param AccountReference $issuer - This is only applicable if using Stripe Connect, and is the connected account
     * that issues the invoice. The invoice is presented with the branding and support information of the specified
     * account.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-subscription_data-invoice_settings-issuer
     */
    public function __construct(AccountReference $issuer)
    {
        $this->m_issuer = $issuer;
    }


    /**
     * @return array{issuer: AccountReferenceArray}
     */
    public function toArray() : array
    {
        return [
            'issuer' => $this->m_issuer->toArray(),
        ];
    }
}
