<?php

/*
 * How a subscription's invoices are paid: charged automatically, or sent to the customer to pay.
 * https://docs.stripe.com/api/subscriptions/create#create_subscription-collection_method
 */

namespace Programster\Stripe\Enums;


enum SubscriptionCollectionMethod: string
{
    case SEND_INVOICE = "send_invoice";

    case CHARGE_AUTOMATICALLY = "charge_automatically";
}
