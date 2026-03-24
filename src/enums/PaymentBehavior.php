<?php

/*
 * Enum for how a subscription item
 * https://docs.stripe.com/api/subscription_items/update?lang=php#update_subscription_item-payment_behavior
 */

namespace Programster\Stripe\Enums;

enum PaymentBehavior: string
{
    /*
     * Use allow_incomplete to transition the subscription to status=past_due if a payment is required but cannot be
     * paid. This allows you to manage scenarios where additional user actions are needed to pay a subscription’s
     * invoice. For example, SCA regulation may require 3DS authentication to complete payment. See the SCA Migration
     * Guide for Billing to learn more. This is the default behavior.
     */
    case ALLOW_INCOMPLETE = "allow_incomplete";


    /*
     * Use default_incomplete to transition the subscription to status=past_due when payment is required and await
     * explicit confirmation of the invoice’s payment intent. This allows simpler management of scenarios where
     * additional user actions are needed to pay a subscription’s invoice. Such as failed payments, SCA regulation, or
     * collecting a mandate for a bank debit payment method.
     */
    case DEFAULT_INCOMPLETE = "default_incomplete";


    /*
     * Use pending_if_incomplete to update the subscription using pending updates. When you use pending_if_incomplete
     * you can only pass the parameters supported by pending updates.
     */
    case PENDING_IF_COMPLETE = "pending_if_incomplete";


    /*
     * Use error_if_incomplete if you want Stripe to return an HTTP 402 status code if a subscription’s invoice cannot
     * be paid. For example, if a payment method requires 3DS authentication due to SCA regulation and further user
     * action is needed, this parameter does not update the subscription and returns an error instead. This was the
     * default behavior for API versions prior to 2019-03-14. See the changelog to learn more.
     */
    case ERROR_IF_INCOMPLETE = "error_if_incomplete";
}