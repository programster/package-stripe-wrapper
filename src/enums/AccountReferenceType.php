<?php

/*
 * Which account an AccountReference refers to.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-automatic_tax-liability-type
 */

namespace Programster\Stripe\Enums;

enum AccountReferenceType: string
{
    // The account making the request to Stripe.
    case SELF = "self";

    // Another (connected) account, whose ID is given alongside.
    case ACCOUNT = "account";
}
