<?php

/*
 * How a checkout session is shown: on a page hosted by Stripe, or embedded in your own site.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-ui_mode
 */

namespace Programster\Stripe\Enums;


enum UiMode: string
{
    // The Checkout Session will be displayed on a hosted page that customers will be redirected to.
    case HOSTED = "hosted";

    // The Checkout Session will be displayed as an embedded form on your website.
    case EMBEDDED = "embedded";
}
