<?php

/*
 * Enum of all the currencies Stripe accepts.
 * https://docs.stripe.com/currencies
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