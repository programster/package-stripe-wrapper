<?php

// Controls how invoices and invoice items display proration amounts and discount amounts.
// https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-subscription_data-billing_mode-flexible-proration_discounts

namespace Programster\Stripe\Enums;

enum ProrationDiscountConfig: string
{
    // Amounts are net of discounts, and discount amounts are zero.
    case INCLUDED = "included";

    // Amounts are gross of discounts, and discount amounts are accurate.
    case ITEMIZED = "itemized";
}