<?php

/*
 * The consent levels a saved payment method can have been saved with, which decide whether it can be shown
 * to the customer again in a checkout session.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-saved_payment_method_options-allow_redisplay_filters
 */

namespace Programster\Stripe\Enums;


enum AllowRedisplayFilters: string
{
    // Indicate that this payment method can always be shown to a customer in a checkout flow.
    case ALWAYS = "always";

    // Indicate that this payment method can’t always be shown to a customer in a checkout flow. For example, it can
    // only be shown in the context of a specific subscription.
    case LIMITED = "limited";

    // This is the default value for payment methods where allow_redisplay wasn’t set.
    case UNSPECIFIED = "unspecified";
}
