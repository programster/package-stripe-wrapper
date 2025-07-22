<?php

/*
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-payment_intent_data-capture_method
 */

namespace Programster\Stripe\Enums;

enum CaptureMethod: string
{
    // Stripe automatically captures funds when the customer authorizes the payment.
    case AUTOMATIC = "automatic";

    // Stripe asynchronously captures funds when the customer authorizes the payment. Recommended over
    // capture_method=automatic due to improved latency. Read the integration guide for more information.
    case AUTOMATIC_ASYNC = "automatic_async";

    // Place a hold on the funds when the customer authorizes the payment, but don’t capture the funds until later.
    // (Not all payment methods support this.)
    case MANUAL = "manual";
}