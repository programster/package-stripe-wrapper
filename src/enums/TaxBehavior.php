<?php

/*
 * Whether a price includes tax or not.
 * https://docs.stripe.com/tax/products-prices-tax-codes-tax-behavior
 */

namespace Programster\Stripe\Enums;


enum TaxBehavior: string
{
    // Tax is to be added on top of the price.
    case EXCLUSIVE = "exclusive";

    // Tax is already included int he price. For example a product has the price defined as 5 USD, the final price the
    // customer pays is 5 USD.
    case INCLUSIVE = "inclusive";

    case UNSPECIFIED = "unspecified";
}
