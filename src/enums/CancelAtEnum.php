<?php

/*
 * Points in a subscription's billing periods at which it can be set to cancel, instead of a timestamp.
 * https://docs.stripe.com/api/subscriptions/create#create_subscription-cancel_at
 */

namespace Programster\Stripe\Enums;


enum CancelAtEnum: string
{
    // Set subscription to cancel at the latest end date among all subscription items’ current billing periods.
    case MAX_PERIOD_END = "max_period_end";

    // Set subscription to cancel at the earliest end date among all subscription items’ current billing periods.
    case MIN_PERIOD_END = "min_period_end";
}
