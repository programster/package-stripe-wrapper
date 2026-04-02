<?php

/*
 * Indicates that you intend to make future payments with the payment method collected by this Checkout Session.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-payment_intent_data-receipt_email
 */

namespace Programster\Stripe\Enums;

enum PaymentIntentSetupFutureUsage: string
{
    // Use off_session if your customer may or may not be present in your checkout flow.
    case OFF_SESSION = "off_session";

    // Use on_session if you intend to only reuse the payment method when your customer is present in your checkout flow.
    case ON_SESSION = "required";
}