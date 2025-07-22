<?php

/*
 * Enum for how prorations are handled.
 * https://docs.stripe.com/billing/subscriptions/prorations
 */

namespace Programster\Stripe\Enums;

enum ProrationBehavior: string
{
    /*
     * Disable creating prorations in current Checkout Session
     */
    case NONE = "none";

    /*
     * Will cause proration invoice items to be created when applicable.
     */
    case CREATE = "create_prorations";
}