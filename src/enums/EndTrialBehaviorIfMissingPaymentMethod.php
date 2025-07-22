<?php

/*
 * An enum for setting Stripe's behaviour if a free trial ends, and the user has not input a payment method.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-subscription_data-trial_settings-end_behavior-missing_payment_method
 */

namespace Programster\Stripe\Enums;

enum EndTrialBehaviorIfMissingPaymentMethod: string
{
    // Cancel the subscription if a payment method is not attached when the trial ends.
    case CANCEL = "cancel";

    // Create an invoice when the trial ends, even if the user did not set up a payment method.
    case CREATE_INVOICE = "create_invoice";

    // Pause the subscription if a payment method is not attached when the trial ends.
    case PAUSE = "pause";
}