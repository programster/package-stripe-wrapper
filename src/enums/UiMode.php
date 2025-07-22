<?php

namespace Programster\Stripe\enums;

enum UiMode : string
{
    // The Checkout Session will be displayed on a hosted page that customers will be redirected to.
    case HOSTED = "hosted";

    // The Checkout Session will be displayed as an embedded form on your website.
    case EMBEDDED = "embedded";
}