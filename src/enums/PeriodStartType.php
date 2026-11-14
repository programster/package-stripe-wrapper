<?php

/*
 * The ways the start of an invoice item's period can be set.
 * https://docs.stripe.com/api/subscriptions/create#create_subscription-add_invoice_items-period-start-type
 */

namespace Programster\Stripe\Enums;


enum PeriodStartType: string
{
    // The period starts at the latest start of the subscription's items' current billing periods.
    case MAX_ITEM_PERIOD_START = "max_item_period_start";

    // The period starts now.
    case NOW = "now";

    // The period starts at a given timestamp.
    case TIMESTAMP = "timestamp";
}
