<?php

/*
 * The status of a checkout session.
 * https://docs.stripe.com/api/checkout/sessions/object#checkout_session_object-status
 */

namespace Programster\Stripe\Enums;

enum CheckoutSessionStatus: string
{
    // The session is still in progress: the customer hasn't paid, and it hasn't expired.
    case OPEN = "open";

    // The customer completed the session, e.g. by paying.
    case COMPLETE = "complete";

    // The session expired before the customer completed it.
    case EXPIRED = "expired";
}
