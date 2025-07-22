<?php

/*
 * Enum of all the currencies Stripe accepts.
 * https://docs.stripe.com/currencies
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