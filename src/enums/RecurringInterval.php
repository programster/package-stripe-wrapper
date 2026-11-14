<?php

/*
 * How often a recurring price is billed, together with an interval count.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-line_items-price_data-recurring-interval
 */

namespace Programster\Stripe\Enums;


enum RecurringInterval: string
{
    case DAY = "day";
    case MONTH = "month";
    case WEEK = "week";
    case YEAR = "year";
}
