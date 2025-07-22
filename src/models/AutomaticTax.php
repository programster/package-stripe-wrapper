<?php

namespace Programster\Stripe\Models;

use Programster\Stripe\Interfaces\Arrayable;

readonly class AutomaticTax implements Arrayable
{
    /**
     * Settings for automatic tax lookup for this session and resulting payments, invoices, and subscriptions.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-automatic_tax
     * @param bool $enabled - Set to true to calculate tax automatically using the customer’s location. Enabling this
     * parameter causes Checkout to collect any billing address information necessary for tax calculation.
     * @param $liability - The account that’s liable for tax when using Stripe Connect (payment split between two
     * accounts). If set, the business address and tax registrations required to perform the tax calculation are loaded
     * from this account. The tax transaction is returned in the report of the connected account. If you are not
     * using Stripe connect, then this should be set to null.
     */
    public function __construct(
        private readonly bool $enabled,
        private ?AccountReference $liability = null
    )
    {

    }

    public function toArray(): array
    {
        $arrayForm = [
            'enabled' => $this->enabled,
        ];

        if ($this->liability !== null)
        {
            $arrayForm['liability'] = $this->liability->toArray();
        }

        return $arrayForm;
    }
}