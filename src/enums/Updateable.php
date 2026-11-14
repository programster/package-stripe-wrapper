<?php

/*
 * Whether a checkout session may update a field of an existing customer.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-customer_update
 */

namespace Programster\Stripe\Enums;


enum Updateable: string
{
    // Checkout will automatically determine whether to update the provided Customer object using details from the
    // session.
    case AUTO = "auto";

    // Checkout will never update the provided Customer object.
    case NEVER = "never";
}
