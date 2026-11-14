<?php

/*
 * Whether a checkout session's payment has been made.
 * https://docs.stripe.com/api/checkout/sessions/object#checkout_session_object-payment_status
 */

namespace Programster\Stripe\Enums;

enum CheckoutSessionPaymentStatus: string
{
    // The payment has been made, and the funds are available.
    case PAID = "paid";

    // The payment hasn't been made yet.
    case UNPAID = "unpaid";

    // No payment was needed, e.g. as the amount was zero, or the session was to set up a payment method.
    case NO_PAYMENT_REQUIRED = "no_payment_required";
}
