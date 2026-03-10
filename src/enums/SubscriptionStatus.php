<?php

/*
 * Subscription statuses
 * https://docs.stripe.com/billing/subscriptions/overview#subscription-statuses
 */

namespace Programster\Stripe\Enums;
enum SubscriptionStatus: string
{
    // fetch all subscriptions regardless of their status
    case ALL = "all";


    // The subscription is currently in a trial period and you can safely provision your product for your customer.
    // The subscription transitions automatically to active when a customer makes the first payment.
    case TRIALING = "trialing";


    // The subscription is in good standing. For past_due subscriptions, paying the latest associated invoice or
    // marking it uncollectible transitions the subscription to active. Note that active doesn’t indicate that all
    // outstanding invoices associated with the subscription have been paid. You can leave other outstanding invoices
    // open for payment, mark them as uncollectible, or void them as you see fit.
    case ACTIVE = "active";


    // 	The customer must make a successful payment within 23 hours to activate the subscription. Or the payment
    // requires action, such as customer authentication. Subscriptions can also be incomplete if there’s a pending
    // payment and the PaymentIntent status is processing.
    case INCOMPLETE = "incomplete";


    // The initial payment on the subscription failed and the customer didn’t make a successful payment within 23 hours
    // of subscription creation. These subscriptions don’t bill customers. This status exists so you can track
    // customers who failed to activate their subscriptions.
    case INCOMPLETE_EXPIRED = "incomplete_expired";


    // Payment on the latest finalised invoice either failed or wasn’t attempted. The subscription continues to create
    // invoices. Your subscription settings determine the subscription’s next state. If the invoice is still unpaid
    // after all attempted smart retries, you can configure the subscription to move to canceled, unpaid, or leave it
    // as past_due. To reactivate the subscription, ask your customer to pay the most recent invoice. The subscription
    // status becomes active regardless of whether the payment is done before or after the latest invoice due date.
    case PAST_DUE = "past_due";


    // The subscription was cancelled. During cancellation, automatic collection for all unpaid invoices is disabled
    // (auto_advance=false). This is a terminal state that can’t be updated.
    case CANCELLED = "cancelled";


    // The latest invoice hasn’t been paid but the subscription remains in place. The latest invoice remains open and
    // invoices continue to generate, but payments aren’t attempted. Revoke access to your product when the
    // subscription is unpaid because payments were already attempted and retried while past_due. To move the
    // subscription to active, pay the most recent invoice before its due date.
    case UNPAID = "unpaid";


    // 	The subscription has ended its trial period without a default payment method and the
    // trial_settings.end_behavior.missing_payment_method is set to pause. Invoices are no longer created for the
    // subscription. After attaching a default payment method to the customer, you can resume the subscription
    case PAUSED = "paused";
}