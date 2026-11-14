<?php

/*
 * The ways the end of an invoice item's period can be set.
 * https://docs.stripe.com/api/subscriptions/create#create_subscription-add_invoice_items-period-end-type
 */

namespace Programster\Stripe\Enums;


enum PeriodEndType: string
{
    // The period ends at the earliest end of the subscription's items' current billing periods.
    case MIN_ITEM_PERIOD_END = "min_item_period_end";

    // The period ends at a given timestamp.
    case TIMESTAMP = "timestamp";
}
