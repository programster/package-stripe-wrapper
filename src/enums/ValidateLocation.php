<?php

/*
 * Enum of all the currencies Stripe accepts.
 * https://docs.stripe.com/currencies
 */

namespace Programster\Stripe\Enums;

enum ValidateLocation: string
{
    case DEFERRED = "deferred";
    case IMMEDIATELY = "immediately";
}