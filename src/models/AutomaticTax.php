<?php

/*
 * Settings for automatic tax lookup.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-automatic_tax
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Interfaces\Arrayable;

readonly class AutomaticTax implements Arrayable
{
    private bool $m_enabled;
    private ?AccountReference $m_liability;


    /**
     * Settings for automatic tax lookup for this session and resulting payments, invoices, and subscriptions.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-automatic_tax
     *
     * @param bool $enabled - Set to true to calculate tax automatically using the customer’s location. Enabling this
     * parameter causes Checkout to collect any billing address information necessary for tax calculation.
     *
     * @param AccountReference|null $liability - The account that’s liable for tax when using Stripe Connect (payment
     * split between two accounts). If set, the business address and tax registrations required to perform the tax
     * calculation are loaded from this account. The tax transaction is returned in the report of the connected
     * account. If you are not using Stripe connect, then this should be set to null.
     */
    public function __construct(bool $enabled, ?AccountReference $liability = null)
    {
        $this->m_enabled = $enabled;
        $this->m_liability = $liability;
    }


    public function toArray() : array
    {
        $arrayForm = [
            'enabled' => $this->m_enabled,
        ];

        if ($this->m_liability !== null)
        {
            $arrayForm['liability'] = $this->m_liability->toArray();
        }

        return $arrayForm;
    }
}
