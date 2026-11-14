<?php

/*
 * The kinds of discount that can be applied to a checkout session.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-discounts
 */

namespace Programster\Stripe\Enums;

enum DiscountType: string
{
    // A promotion code, which the customer could otherwise type in themselves.
    case PROMOTION_CODE = "promotion_code";

    // A coupon, applied directly.
    case COUPON = "coupon";
}
