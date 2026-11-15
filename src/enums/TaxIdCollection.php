<?php

/*
 * Whether a checkout session collects the customer's tax ID, and whether they must give one. To not collect it, leave
 * the checkout methods' $taxIdCollection out.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-tax_id_collection
 */

namespace Programster\Stripe\Enums;


enum TaxIdCollection: string
{
    // Checkout asks for the customer's tax ID, but they don't have to give one.
    case OPTIONAL = "never";

    // Checkout asks for the customer's tax ID, and they must give one, if Stripe can collect tax IDs for the country
    // of their billing address.
    case REQUIRED_WHERE_SUPPORTED = "if_supported";


    /**
     * Get the setting in the form Stripe takes.
     * @return array{enabled: true, required: string}
     */
    public function toStripeArrayForm() : array
    {
        return ['enabled' => true, 'required' => $this->value];
    }
}
