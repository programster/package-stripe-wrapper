<?php

// Configure whether a Checkout Session creates a Customer during Session confirmation.
// https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-customer_creation

namespace Programster\Stripe\Enums;

enum CustomerCreation: string
{
    // The Checkout Session will always create a Customer when a Session confirmation is attempted.
    case ALWAYS = "always";

    // The Checkout Session will only create a Customer if it is required for Session confirmation. Currently, only
    // subscription mode Sessions and payment mode Sessions with post-purchase invoices enabled require a Customer.
    case IF_REQUIRED = "if_required";
}