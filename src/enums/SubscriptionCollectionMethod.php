<?php

/*
 * Subscription statuses
 * https://docs.stripe.com/billing/subscriptions/overview#subscription-statuses
 */

namespace Programster\Stripe\Enums;
enum SubscriptionCollectionMethod: string
{
    case SEND_INVOICE = "send_invoice";

    case CHARGE_AUTOMATICALLY = "charge_automatically";
}