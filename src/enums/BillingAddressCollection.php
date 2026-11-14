<?php

/*
 * Whether a checkout session should collect the customer's billing address.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-billing_address_collection
 */

namespace Programster\Stripe\Enums;


enum BillingAddressCollection: string
{
    // Checkout will only collect the billing address when necessary. When using automatic_tax, Checkout will collect
    // the minimum number of fields required for tax calculation.
    case AUTO = "auto";

    // Always collect the customer's billing address
    case REQUIRED = "required";
}
