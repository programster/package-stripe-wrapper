<?php

/*
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-payment_intent_data-capture_method
 */

namespace Programster\Stripe\Enums;

enum CancelAtEnum: string
{
    // Set subscription to cancel at the latest end date among all subscription items’ current billing periods.
    case MAX_PERIOD_END = "max_period_end";

    // Set subscription to cancel at the earliest end date among all subscription items’ current billing periods.
    case MIN_PERIOD_END = "min_period_end";
}