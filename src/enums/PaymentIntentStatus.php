<?php

/*
 * The status of a payment intent.
 * https://docs.stripe.com/api/payment_intents/object#payment_intent_object-status
 */

namespace Programster\Stripe\Enums;

enum PaymentIntentStatus: string
{
    case REQUIRES_PAYMENT_METHOD = "requires_payment_method";
    case REQUIRES_CONFIRMATION = "requires_confirmation";
    case REQUIRES_ACTION = "requires_action";
    case PROCESSING = "processing";

    // The payment has been authorised, and is waiting to be captured.
    case REQUIRES_CAPTURE = "requires_capture";
    case CANCELED = "canceled";
    case SUCCEEDED = "succeeded";
}
