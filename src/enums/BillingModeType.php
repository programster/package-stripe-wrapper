<?php

// Controls the calculation and orchestration of prorations and invoices for subscriptions. If no value is passed, the default is flexible.
// https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-subscription_data-billing_mode-type

namespace Programster\Stripe\Enums;

enum BillingModeType: string
{
    // Calculations for subscriptions and invoices are based on legacy defaults.
    case CLASSIC = "always";

    // Supports more flexible calculation and orchestration options for subscriptions and invoices.
    case FLEXIBLE = "flexible";
}