<?php

// Options for configuring if an embedded flow should redirect to the return url on completion.
// https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-redirect_on_completion

namespace Programster\Stripe\Enums;

enum RedirectOnCompletion: string
{
    // The Session will always redirect to the return_url after successful confirmation.
    case ALWAYS = "always";

    // The Session will only redirect to the return_url after a redirect-based payment method is used.
    case IF_REQUIRED = "if_required";

    // The Session will never redirect to the return_url, and redirect-based payment methods will be
    case NEVER = "never";
}