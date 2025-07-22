<?php

/*
 * Enum for tax behavior. Refer to:
 * https://docs.stripe.com/tax/products-prices-tax-codes-tax-behavior#setting-a-default-tax-behavior-(recommended)
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